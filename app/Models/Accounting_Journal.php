<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Accounting_Journal extends Model
{
    //
    protected $table = 'accounting_journals';

    protected $fillable = [
        'transaction_id',
        'account_code',
        'position',
        'amount'
    ];

    //relasi ke transaksi
    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }
}
