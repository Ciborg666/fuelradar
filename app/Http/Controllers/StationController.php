<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Station;
use Illuminate\Http\Request;

class StationController extends Controller
{
    /**
     * Предложить новую АЗС
     */
    public function suggest(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'brand' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'city' => 'nullable|string|max:100',
        ]);
        
        // В реальном приложении здесь нужно отправить уведомление администратору
        // или создать запись в таблице pending_stations
        
        // Для простоты создаем станцию сразу (в продакшене лучше модерацию)
        $brand = Brand::firstOrCreate(
            ['name' => $validated['brand']],
            ['color_hex' => '#666666']
        );
        
        $station = Station::create([
            'brand_id' => $brand->id,
            'name' => $validated['name'],
            'address' => $validated['address'],
            'lat' => $validated['lat'],
            'lng' => $validated['lng'],
            'city' => $validated['city'] ?? null,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'АЗС успешно добавлена',
            'station' => $station,
        ]);
    }
}