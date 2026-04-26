<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $fillable = [
        'transaction_number',
        'date',
        'supplier_name',
        'product',
        'quantity',
        'total',
        'notes',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'total' => 'float',
    ];
}
