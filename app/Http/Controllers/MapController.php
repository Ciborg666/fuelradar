<?php

namespace App\Http\Controllers;

use App\Models\FuelType;
use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MapController extends Controller
{
    /**
     * Главная страница с картой
     */
    public function index()
    {
        $brands = \App\Models\Brand::all();
        $fuelTypes = FuelType::all();
        
        return view('map.index', compact('brands', 'fuelTypes'));
    }
    
    /**
     * API: Получить ближайшие станции
     */
    public function getStations(Request $request)
        {
            $validated = $request->validate([
                'lat' => 'required|numeric|between:-90,90',
                'lng' => 'required|numeric|between:-180,180',
                'radius' => 'nullable|numeric|max:50',
                'status' => 'nullable|in:has_fuel,queue,low_fuel,no_fuel',
                'fuel_type' => 'nullable|string', // ← Новый параметр
            ]);

            $radius = $validated['radius'] ?? 15;

            $query = Station::nearby($validated['lat'], $validated['lng'], $radius)
                ->with(['brand', 'reports' => function($query) {
                    $query->where('expires_at', '>', now())
                        ->orderBy('created_at', 'desc')
                        ->limit(1);
                }]);

            // Фильтр по статусу
            if (!empty($validated['status'])) {
                $query->whereHas('reports', function($q) use ($validated) {
                    $q->where('status', $validated['status'])
                    ->where('expires_at', '>', now());
                });
            }

            // Фильтр по типу топлива
            if (!empty($validated['fuel_type'])) {
                $query->whereHas('reports', function($q) use ($validated) {
                    $q->where('expires_at', '>', now())
                    ->whereJsonContains('fuel_types', $validated['fuel_type']);
                });
            }

            $stations = $query->limit(100)->get()->map(function($station) {
                $latestReport = $station->reports->first();
                
                return [
                    'id' => $station->id,
                    'name' => $station->brand->name . ' ' . $station->name,
                    'address' => $station->address,
                    'brand_name' => $station->brand->name,
                    'brand_color' => $station->brand->color_hex,
                    'lat' => (float)$station->lat,
                    'lng' => (float)$station->lng,
                    'distance' => round($station->distance, 1),
                    'status' => $latestReport?->status ?? 'unknown',
                    'confidence' => $latestReport?->confidence_score ?? 0,
                    'verified_count' => $latestReport?->verified_count ?? 0,
                    'fuel_types' => $latestReport?->fuel_types ?? [],
                    'reports_count' => $station->reports()->where('created_at', '>', now()->subHours(8))->count(),
                    'updated_at' => $latestReport?->created_at?->diffForHumans() ?? 'Нет данных',
                ];
            });

            return response()->json($stations);
        }
}