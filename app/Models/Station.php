<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Station extends Model
{
    protected $fillable = [
        'brand_id',
        'name',
        'address',
        'lat',
        'lng',
        'city',
    ];
    
    protected $casts = [
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
    ];
    
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
    
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
    
    /**
     *_scope для поиска ближайших станций
     */
    public function scopeNearby($query, $lat, $lng, $radiusKm = 10)
    {
        return $query->selectRaw("
            *, 
            (6371 * acos(
                cos(radians(?)) * cos(radians(lat)) * 
                cos(radians(lng) - radians(?)) + 
                sin(radians(?)) * sin(radians(lat))
            )) AS distance
        ", [$lat, $lng, $lat])
        ->having('distance', '<=', $radiusKm)
        ->orderBy('distance');
    }
    
    /**
     * Получить последний активный отчет
     */
    public function getLatestActiveReportAttribute()
    {
        return $this->reports()
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->first();
    }
}