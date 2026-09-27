<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    //
    protected $table = 'transactions';

    protected $fillable = [
        'transaction_code',
        'customer_id',
        'user_id',
        'transaction_date',
        'transaction_type',
        'amount',
        'description'
    ];

    //relasi ke nasabah
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    //relasi ke petugas teller
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    //relasi ke pembukuan jurnal
    public function journals()
    {
        return $this->hasMany(Accounting_Journal::class, 'transaction_id');
    }
}
