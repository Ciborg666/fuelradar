<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Report extends Model
{
    protected $fillable = [
        'station_id',
        'user_id',
        'ip_hash',
        'status',
        'fuel_types',
        'queue_size',
        'confidence_score',
        'verified_count',
        'expires_at',
    ];
    
    protected $casts = [
        'fuel_types' => 'array',
        'expires_at' => 'datetime',
    ];
    
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function votes(): HasMany
    {
        return $this->hasMany(ReportVote::class);
    }
    
    /**
     * Проверить, актуален ли отчет
     */
    public function isActual(): bool
    {
        return $this->expires_at->isFuture();
    }
    
    /**
     * Получить текст статуса
     */
    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'has_fuel' => 'Есть топливо',
            'queue' => 'Очередь',
            'low_fuel' => 'Мало топлива',
            'no_fuel' => 'Нет топлива',
            default => 'Неизвестно',
        };
    }
}