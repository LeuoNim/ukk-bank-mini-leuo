<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('account_number')->unique(); // nomor rekening: AK-0001, RPL-0001, dll
            $table->string('student_id')->unique(); // NIS siswa
            $table->string('student_name');
            $table->string('class'); // kelas: XII AKL 1, dll
            $table->decimal('balance', 15, 2)->default(0); // saldo
            $table->string('pin');
            $table->string('status')->default('Aktif'); // Aktif / NonAktif
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
