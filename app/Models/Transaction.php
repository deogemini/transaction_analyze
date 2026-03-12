<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'statement_id',
        'transaction_date',
        'from',
        'to',
        'amount',
        'balance',
        'description',
        'charge',
        'type',
        'category',
        'is_charge_row',
    ];

    public function statement()
    {
        return $this->belongsTo(Statement::class);
    }
}
