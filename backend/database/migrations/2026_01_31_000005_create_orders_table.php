<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->string('contact')->nullable();
            // Delivery info
            $table->string('country')->default('egypt');
            $table->string('full_name');
            $table->text('address_details')->nullable();
            $table->string('governorate');
            $table->string('phone');
            $table->string('phone2')->nullable();
            // Order details
            $table->string('shipping_method')->default('standard');
            $table->string('payment_method')->default('cod');
            $table->string('billing_address')->default('same');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('shipping', 10, 2)->default(60);
            $table->decimal('total', 10, 2);
            $table->enum('status', ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
