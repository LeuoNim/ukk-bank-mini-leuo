<?php

namespace App\Services;

use App\Http\Controllers\Api\CustomerController;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CustomerOnboarding
{
    public function register(string $nis, string $name, string $class): Customer
    {
        return DB::transaction(fn () => Customer::create([
            'student_id' => $nis,
            'student_name' => $name,
            'class' => $class,
            'account_number' => CustomerController::generateAccountNumber($class),
            'balance' => 0,
            'status' => Customer::STATUS_PENDING,
            'password' => Hash::make(Str::random(32)),
            'pin' => Hash::make(Str::random(32)),
        ]));
    }
}
