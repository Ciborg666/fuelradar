<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportVote extends Model
{
    protected $fillable = [
        'report_id',
        'ip_hash',
        'vote',
    ];
    
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }
}