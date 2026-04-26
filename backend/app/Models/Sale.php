<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'transaction_number',
        'date',
        'customer_name',
        'product',
        'quantity',
        'unit_price',
        'total',
        'debtor',
        'creditor',
        'notes',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'total' => 'float',
        'debtor' => 'float',
        'creditor' => 'float',
    ];
}
