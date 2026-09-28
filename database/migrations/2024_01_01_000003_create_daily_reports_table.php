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
        Schema::create('daily_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teller_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->date('report_date');
            $table->bigInteger('total_deposit')->default(0);
            $table->bigInteger('total_withdrawal')->default(0);
            $table->string('approval_status')->default('Pending'); // Pending / Approved
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_reports');
    }
};
