<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    protected $fillable = [
        'name',
        'logo_url',
        'color_hex',
    ];
    
    public function stations(): HasMany
    {
        return $this->hasMany(Station::class);
    }
}