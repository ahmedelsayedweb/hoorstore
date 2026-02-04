<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ChangeSizeToSizesInCartAndOrderItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Update cart_items table
        Schema::table('cart_items', function (Blueprint $table) {
            $table->json('sizes')->nullable()->after('color');
        });

        // Migrate existing data for cart_items
        DB::table('cart_items')->whereNotNull('size')->get()->each(function ($item) {
            DB::table('cart_items')
                ->where('id', $item->id)
                ->update(['sizes' => json_encode([$item->size])]);
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn('size');
        });

        // Update order_items table
        Schema::table('order_items', function (Blueprint $table) {
            $table->json('sizes')->nullable()->after('color');
        });

        // Migrate existing data for order_items
        DB::table('order_items')->whereNotNull('size')->get()->each(function ($item) {
            DB::table('order_items')
                ->where('id', $item->id)
                ->update(['sizes' => json_encode([$item->size])]);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('size');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert cart_items table
        Schema::table('cart_items', function (Blueprint $table) {
            $table->string('size')->nullable()->after('color');
        });

        // Migrate data back for cart_items
        DB::table('cart_items')->whereNotNull('sizes')->get()->each(function ($item) {
            $sizes = json_decode($item->sizes, true);
            $size = is_array($sizes) && count($sizes) > 0 ? $sizes[0] : null;
            DB::table('cart_items')
                ->where('id', $item->id)
                ->update(['size' => $size]);
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn('sizes');
        });

        // Revert order_items table
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('size')->nullable()->after('color');
        });

        // Migrate data back for order_items
        DB::table('order_items')->whereNotNull('sizes')->get()->each(function ($item) {
            $sizes = json_decode($item->sizes, true);
            $size = is_array($sizes) && count($sizes) > 0 ? $sizes[0] : null;
            DB::table('order_items')
                ->where('id', $item->id)
                ->update(['size' => $size]);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('sizes');
        });
    }
}
