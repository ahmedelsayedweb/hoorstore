<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // First convert existing string values to JSON arrays
        $products = DB::table('products')->get();
        foreach ($products as $product) {
            $category = $product->category;
            // If it's already a valid JSON array, skip
            $decoded = json_decode($category);
            if (is_array($decoded)) {
                continue;
            }
            // Convert string to JSON array
            $jsonArray = json_encode($category ? [$category] : []);
            DB::table('products')->where('id', $product->id)->update(['category' => $jsonArray]);
        }

        // Change column type from string to json using raw SQL
        DB::statement('ALTER TABLE products MODIFY category JSON NULL');
    }

    public function down(): void
    {
        // Change back to string first
        DB::statement('ALTER TABLE products MODIFY category VARCHAR(255) NULL');

        // Convert JSON arrays back to first string value
        $products = DB::table('products')->get();
        foreach ($products as $product) {
            $decoded = json_decode($product->category, true);
            $value = is_array($decoded) && count($decoded) > 0 ? $decoded[0] : '';
            DB::table('products')->where('id', $product->id)->update(['category' => $value]);
        }
    }
};
