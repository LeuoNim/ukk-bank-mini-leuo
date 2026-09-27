<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; 
use Illuminate\Foundation\Auth\User as Authenticatable; 
use Laravel\Sanctum\HasApiTokens;

class CustomerAccount extends Authenticatable
{
    //
    use HasApiTokens, HasFactory;

    protected $table = 'customer_accounts';

    protected $fillable = [
        'customer_Id',
        'account_number',
        'username',
        'password',
        'balance',
        'status',
        'failed_login_attempts',
        'last_login_at'
    ];

    protected $casts = [
        'last_login_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
    ];
}
