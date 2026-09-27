<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasApiTokens;

    //memaksa tabel model membaca tabel
    protected $table = 'customers';

    protected $fillable = [
        'account_number',
        'student_id',
        'student_name',
        'class',
        'balance',
        'pin'
    ];

    protected $hidden = [
        'pin'
    ];

    //relasi ke transaksi nasabah
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'customer_id');
    }
}
