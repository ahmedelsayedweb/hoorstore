<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddDebtorCreditorToSales extends Migration
{
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('debtor', 12, 2)->nullable()->after('total');   // المدين
            $table->decimal('creditor', 12, 2)->nullable()->after('debtor'); // الدائن
        });

        // Move numeric notes values to debtor column
        DB::statement("
            UPDATE sales
            SET debtor = CAST(notes AS DECIMAL(12,2)),
                notes = NULL
            WHERE notes IS NOT NULL
              AND notes REGEXP '^-?[0-9]+(\\.[0-9]+)?$'
        ");
    }

    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['debtor', 'creditor']);
        });
    }
}
