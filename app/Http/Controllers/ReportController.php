<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportVote;
use App\Models\Station;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    /**
     * Создать новый отчет
     */
    public function store(Request $request, Station $station): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:has_fuel,queue,low_fuel,no_fuel',
            'fuel_types' => 'required|array|min:1',
            'fuel_types.*' => 'string',
            'queue_size' => 'nullable|string|max:50',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        // Проверка расстояния (можно отключить через .env)
        if (!env('SKIP_DISTANCE_CHECK', false)) {
            $distance = $this->calculateDistance(
                (float) $validated['lat'],
                (float) $validated['lng'],
                (float) $station->lat,
                (float) $station->lng
            );

            if ($distance > 0.5) {
                return response()->json([
                    'error' => 'Вы находитесь слишком далеко от этой АЗС (макс. 500м)'
                ], 422);
            }
        }

        // Хеш IP для защиты от спама
        $ipHash = hash('sha256', $request->ip());

        // Проверка: не более 5 отчетов за последний час с этого IP
        $recentReports = Report::where('ip_hash', $ipHash)
            ->where('created_at', '>', now()->subHour())
            ->count();

        if ($recentReports >= 5) {
            return response()->json([
                'error' => 'Слишком много отчетов. Попробуйте позже.'
            ], 429);
        }

        // Получаем ID пользователя (может быть null для анонимов)
        $userId = Auth::id();

        // Создаем отчет
        $report = Report::create([
            'station_id' => $station->id,
            'user_id' => $userId,
            'ip_hash' => $ipHash,
            'status' => $validated['status'],
            'fuel_types' => $validated['fuel_types'],
            'queue_size' => $validated['queue_size'] ?? null,
            'confidence_score' => 0,
            'verified_count' => 0,
            'expires_at' => now()->addHours(24),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Отчет успешно создан',
            'report' => $report,
        ]);
    }

    /**
     * Проголосовать за отчет (подтвердить/опровергнуть)
     */
    public function vote(Request $request, Report $report): JsonResponse
    {
        $validated = $request->validate([
            'vote' => 'required|in:up,down',
        ]);

        $ipHash = hash('sha256', $request->ip());

        // Проверка: не голосовал ли уже этот IP
        $existingVote = ReportVote::where('report_id', $report->id)
            ->where('ip_hash', $ipHash)
            ->first();

        if ($existingVote) {
            return response()->json([
                'error' => 'Вы уже голосовали за этот отчет'
            ], 422);
        }

        // Создаем голос
        ReportVote::create([
            'report_id' => $report->id,
            'ip_hash' => $ipHash,
            'vote' => $validated['vote'],
        ]);

        // Обновляем счетчики отчета
        if ($validated['vote'] === 'up') {
            $report->increment('verified_count');
            $report->increment('confidence_score', 10);
        } else {
            $report->decrement('confidence_score', 15);
        }

        return response()->json([
            'success' => true,
            'message' => $validated['vote'] === 'up' ? 'Отчет подтвержден' : 'Отчет опровергнут',
            'verified_count' => $report->fresh()->verified_count,
            'confidence_score' => $report->fresh()->confidence_score,
        ]);
    }

    /**
     * Рассчитать расстояние между двумя точками (формула Haversine)
     * 
     * @return float расстояние в километрах
     */
    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371.0; // радиус Земли в км

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}