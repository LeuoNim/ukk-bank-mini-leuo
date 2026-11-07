<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyReport extends Model
{
    protected $table = 'daily_reports';

    protected $fillable = [
        'teller_id',
        'supervisor_id',
        'report_date',
        'total_deposit',
        'total_withdrawal',
        'approval_status',
        'notes',
    ];

    // relasi ke teller pembuat laporan
    public function teller()
    {
        return $this->belongsTo(User::class, 'teller_id');
    }

    // relasi ke supervisor yang menyetujui
    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }
}
