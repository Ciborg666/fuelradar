<?php

use App\Http\Controllers\MapController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Главная страница
Route::get('/', [MapController::class, 'index'])->name('home');

/*
|--------------------------------------------------------------------------
| API Routes (для AJAX запросов)
|--------------------------------------------------------------------------
*/

// Получить станции
Route::get('/api/stations', [MapController::class, 'getStations'])->name('api.stations');

// Создать отчет
Route::post('/api/stations/{station}/report', [ReportController::class, 'store'])
    ->name('api.reports.store');

// Проголосовать за отчет
Route::post('/api/reports/{report}/vote', [ReportController::class, 'vote'])
    ->name('api.reports.vote');

// Предложить новую АЗС
Route::post('/api/stations/suggest', [StationController::class, 'suggest'])
    ->name('api.stations.suggest');