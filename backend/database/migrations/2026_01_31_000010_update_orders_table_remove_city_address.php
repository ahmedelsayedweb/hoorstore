<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add new columns
        Schema::table('orders', function (Blueprint $table) {
            $table->string('full_name')->after('country')->nullable();
            $table->text('address_details')->nullable()->after('full_name');
            $table->string('phone2')->nullable()->after('phone');
        });

        // Copy data from old columns to new ones
        DB::statement("UPDATE orders SET full_name = CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))");
        DB::statement("UPDATE orders SET address_details = CONCAT(COALESCE(address, ''), ', ', COALESCE(city, ''))");

        // Drop old columns
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'address', 'city', 'postal_code']);
        });
    }

    public function down(): void
    {
        // Add back old columns
        Schema::table('orders', function (Blueprint $table) {
            $table->string('first_name')->after('country')->nullable();
            $table->string('last_name')->after('first_name')->nullable();
            $table->text('address')->after('last_name')->nullable();
            $table->string('city')->after('address')->nullable();
            $table->string('postal_code')->nullable()->after('governorate');
        });

        // Drop new columns
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['full_name', 'address_details', 'phone2']);
        });
    }
};
