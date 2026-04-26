<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SaleSeeder extends Seeder
{
    public function run()
    {
        $data = require __DIR__ . '/csv/sales_data.php';

        DB::table('sales')->truncate();

        $now = date('Y-m-d H:i:s');
        $rows = array_map(function ($row) use ($now) {
            return array_merge($row, ['created_at' => $now, 'updated_at' => $now]);
        }, $data);

        foreach (array_chunk($rows, 50) as $chunk) {
            DB::table('sales')->insert($chunk);
        }

        echo "Inserted " . count($rows) . " sales.\n";
    }
}
