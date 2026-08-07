<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    /**
     * Create a pending order from the user's cart and initialize a Paystack transaction.
     */
    public function checkout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shipping_address' => 'required|array',
            'shipping_address.line1' => 'required|string',
            'shipping_address.city' => 'required|string',
            'shipping_address.state' => 'required|string',
            'coupon_code' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $cartItems = CartItem::with(['product', 'variant'])->where('user_id', $user->id)->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 422);
        }

        // Verify stock before creating the order
        foreach ($cartItems as $item) {
            $stock = $item->variant ? $item->variant->stock : $item->product->stock_quantity;
            if ($stock < $item->quantity) {
                return response()->json([
                    'message' => "Insufficient stock for {$item->product->name}",
                ], 422);
            }
        }

        $subtotal = $cartItems->sum(fn (CartItem $item) => $item->lineTotal());

        $discount = 0;
        $coupon = null;
        if ($request->filled('coupon_code')) {
            $coupon = Coupon::where('code', $request->coupon_code)->first();
            if (! $coupon || ! $coupon->isValid()) {
                return response()->json(['message' => 'Invalid or expired coupon'], 422);
            }
            $discount = $coupon->calculateDiscount($subtotal);
        }

        $total = max(0, $subtotal - $discount);
        $reference = 'PSK-' . strtoupper(Str::random(12));

        $order = DB::transaction(function () use ($user, $cartItems, $subtotal, $discount, $total, $coupon, $request, $reference) {
            $order = Order::create([
                'user_id' => $user->id,
                'coupon_id' => $coupon?->id,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'shipping_address' => $request->shipping_address,
                'payment_reference' => $reference,
            ]);

            foreach ($cartItems as $item) {
                $unitPrice = $item->variant ? $item->variant->finalPrice() : (float) $item->product->price;
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'quantity' => $item->quantity,
                    'price' => $unitPrice,
                ]);
            }

            Payment::create([
                'order_id' => $order->id,
                'reference' => $reference,
                'status' => 'pending',
                'amount' => $total,
            ]);

            return $order;
        });

        // Initialize Paystack transaction (amount in kobo)
        $response = Http::withToken(config('services.paystack.secret_key'))
            ->post(config('services.paystack.payment_url') . '/transaction/initialize', [
                'email' => $user->email,
                'amount' => (int) round($total * 100),
                'reference' => $reference,
                'callback_url' => config('app.frontend_url') . '/checkout/callback',
            ]);

        if (! $response->successful()) {
            Log::error('Paystack init failed', ['response' => $response->body()]);
            return response()->json(['message' => 'Could not initialize payment'], 502);
        }

        return response()->json([
            'order' => $order->load('items'),
            'paystack' => $response->json('data'),
        ], 201);
    }

    /**
     * Paystack webhook: verify signature, mark order paid, decrement stock, generate tracking number.
     */
    public function webhook(Request $request)
    {
        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();
        $expected = hash_hmac('sha512', $payload, config('services.paystack.secret_key'));

        if (! $signature || ! hash_equals($expected, $signature)) {
            Log::warning('Paystack webhook signature mismatch');
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $event = $request->input('event');
        $data = $request->input('data');

        if ($event === 'charge.success') {
            $reference = $data['reference'] ?? null;
            $payment = Payment::where('reference', $reference)->first();

            if ($payment && $payment->status !== 'success') {
                DB::transaction(function () use ($payment, $data) {
                    $payment->update([
                        'status' => 'success',
                        'channel' => $data['channel'] ?? null,
                        'metadata' => $data,
                        'paid_at' => now(),
                    ]);

                    $order = $payment->order;
                    $order->update([
                        'status' => 'paid',
                        'tracking_number' => Order::generateTrackingNumber(),
                    ]);

                    // Decrement stock for each order item
                    foreach ($order->items as $item) {
                        if ($item->variant_id) {
                            $item->variant->decrement('stock', $item->quantity);
                        } else {
                            $item->product->decrementStock($item->quantity);
                        }
                    }

                    if ($order->coupon_id) {
                        $order->coupon->increment('used');
                    }

                    // Clear the user's cart after successful payment
                    CartItem::where('user_id', $order->user_id)->delete();
                });
            }
        }

        return response()->json(['message' => 'Webhook processed']);
    }
}
