<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->enum('status', ['pending', 'success', 'failed', 'abandoned'])->default('pending');
            $table->decimal('amount', 12, 2);
            $table->string('channel')->nullable(); // card, bank, ussd, etc from Paystack
            $table->json('metadata')->nullable(); // raw Paystack response payload
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
