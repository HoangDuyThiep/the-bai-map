<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportHelpfulVote extends Model
{
    protected $fillable = [
        'sales_report_id',
        'user_id',
    ];

    public function salesReport()
    {
        return $this->belongsTo(SalesReport::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
