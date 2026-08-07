<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'coupon_id',
        'status',
        'subtotal',
        'discount',
        'total',
        'tracking_number',
        'shipping_address',
        'payment_reference',
    ];

    protected function casts(): array
    {
        return [
            'shipping_address' => 'array',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public static function generateTrackingNumber(): string
    {
        do {
            $candidate = 'SHIP-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));
        } while (self::where('tracking_number', $candidate)->exists());

        return $candidate;
    }
}
