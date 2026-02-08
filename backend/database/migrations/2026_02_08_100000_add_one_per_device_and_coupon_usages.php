<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOnePerDeviceAndCouponUsages extends Migration
{
    public function up()
    {
        // Add one_per_device flag to coupons table
        Schema::table('coupons', function (Blueprint $table) {
            $table->boolean('one_per_device')->default(false)->after('usage_limit');
        });

        // Create coupon_usages table to track per-device usage
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('coupon_id');
            $table->string('browser_id');
            $table->timestamps();

            $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('cascade');
            $table->unique(['coupon_id', 'browser_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('coupon_usages');

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn('one_per_device');
        });
    }
}
