<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Statement extends Model
{
    protected $fillable = [
        'user_id',
        'file_name',
        'file_path',
        'provider',
        'type',
        'start_date',
        'end_date',
        'status',
        'total_debits',
        'total_credits',
        'total_charges',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
