<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    public const STATUS_PENDING = 'BelumAktif';

    use HasApiTokens, SoftDeletes;

    // memaksa tabel model membaca tabel
    protected $table = 'customers';

    protected $fillable = [
        'account_number',
        'student_id',
        'student_name',
        'class',
        'balance',
        'password',
        'pin',
        'status',
    ];

    protected $hidden = [
        'password',
        'pin',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function getAuthPassword()
    {
        return $this->password ?? $this->pin;
    }

    public function hasCustomPassword(): bool
    {
        return ! empty($this->password);
    }

    // relasi ke transaksi nasabah
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'customer_id');
    }
}
