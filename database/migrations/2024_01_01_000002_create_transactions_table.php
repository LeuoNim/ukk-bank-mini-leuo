<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_code')->unique(); // DEP-xxx, WDR-xxx
            $table->foreignId('customer_id')->constrained('customers')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict'); // teller yang memproses
            $table->dateTime('transaction_date');
            $table->string('transaction_type'); // Setoran / Penarikan
            $table->bigInteger('amount');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Menambahkan CHECK CONSTRAINT untuk memastikan nominal transaksi tidak minus / 0
        DB::statement('ALTER TABLE transactions ADD CONSTRAINT chk_amount_gt_zero CHECK (amount > 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
