<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Buat Akun Petugas
        \App\Models\User::create([
            'username' => 'admin',
            'staff_name' => 'Administrator Utama',
            'password' => bcrypt('password'),
            'role' => 'Administrator',
        ]);

        \App\Models\User::create([
            'username' => 'supervisor',
            'staff_name' => 'Supervisor Bank',
            'password' => bcrypt('password'),
            'role' => 'Supervisor',
        ]);

        $teller = \App\Models\User::create([
            'username' => 'teller',
            'staff_name' => 'Teller Satu',
            'password' => bcrypt('password'),
            'role' => 'Teller',
        ]);

        // 2. Buat Akun Nasabah Contoh
        \App\Models\Customer::create([
            'account_number' => 'AK-0001',
            'student_id' => '2024001',
            'student_name' => 'Ahmad Ramadhan',
            'class' => 'XII AKL 1',
            'balance' => 1500000,
            'pin' => bcrypt('123456'),
            'status' => 'Aktif',
        ]);

        \App\Models\Customer::create([
            'account_number' => 'RPL-0001',
            'student_id' => '2024002',
            'student_name' => 'Siti Nurbaya',
            'class' => 'XI RPL 2',
            'balance' => 620000,
            'pin' => bcrypt('123456'),
            'status' => 'Aktif',
        ]);
    }
}
