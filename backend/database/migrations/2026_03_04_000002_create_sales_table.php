<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesTable extends Migration
{
    public function up()
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->integer('transaction_number')->nullable();
            $table->date('date')->nullable();
            $table->string('customer_name')->nullable();
            $table->text('product')->nullable();
            $table->string('quantity')->nullable();
            $table->string('unit_price')->nullable();
            $table->decimal('total', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sales');
    }
}
