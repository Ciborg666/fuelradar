@extends('layouts.app')

@section('title', 'FuelRadar — Где есть бензин?')

@section('content')
<div class="relative h-screen w-screen">
    
    {{-- КАРТА --}}
    <div id="map" class="absolute inset-0 z-0"></div>

    {{-- ВЕРХНЯЯ ПАНЕЛЬ --}}
    <div class="absolute top-4 left-4 right-4 z-10 flex justify-between items-start pointer-events-none gap-3">
        
        {{-- Левая часть --}}
        <div class="flex flex-col gap-3 pointer-events-auto flex-1 max-w-md">
            <div class="glass rounded-2xl p-4 shadow-xl">
                <h1 class="text-2xl font-black logo-gradient">⛽ FuelRadar</h1>
                <p class="text-xs text-slate-400 mt-1">Народная карта заправок</p>
            </div>
            
            {{-- Поиск --}}
            <div class="glass rounded-2xl p-3 shadow-xl search-container">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input type="text" id="searchInput" placeholder="Найти город..." 
                        class="flex-1 bg-transparent outline-none text-sm placeholder-slate-500 text-white">
                    <button id="clearSearch" class="text-slate-400 hover:text-white hidden">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div id="searchResults" class="search-results glass rounded-xl mt-2 overflow-hidden hidden"></div>
            </div>
        </div>
        
        {{-- Правая часть --}}
        <div class="flex flex-col gap-3 pointer-events-auto">
            <div class="glass rounded-2xl p-3 shadow-xl flex items-center gap-2">
                <span class="text-sm">🌙</span>
                <button id="themeToggle" class="relative w-14 h-7 rounded-full transition-colors duration-300 bg-slate-700">
                    <span id="themeToggleCircle" class="absolute top-1 left-1 w-5 h-5 rounded-full bg-white shadow-md transition-transform duration-300 flex items-center justify-center text-xs">🌙</span>
                </button>
                <span class="text-sm">☀️</span>
            </div>
            
            <button id="findBestBtn" class="glass rounded-2xl p-4 shadow-xl hover:bg-slate-800/80 transition flex items-center gap-2 group">
                <svg class="w-6 h-6 text-indigo-400 group-hover:rotate-45 transition duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                <span class="font-semibold hidden sm:block text-white">Найти лучшую</span>
            </button>
            
            <button id="myLocationBtn" class="glass rounded-2xl p-4 shadow-xl hover:bg-slate-800/80 transition flex items-center gap-2 group">
                <svg class="w-6 h-6 text-blue-400 group-hover:scale-110 transition duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span class="font-semibold hidden sm:block text-white">Я здесь</span>
            </button>

            {{-- Кнопка для принудительного запроса геолокации --}}
            <button id="requestLocationBtn" class="glass rounded-2xl p-4 shadow-xl hover:bg-slate-800/80 transition flex items-center gap-2 group hidden">
                <svg class="w-6 h-6 text-emerald-400 group-hover:scale-110 transition duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
                <span class="font-semibold hidden sm:block text-white">Разрешить геолокацию</span>
            </button>

        </div>
    </div>

    {{-- НИЖНЯЯ ПАНЕЛЬ --}}
    <div class="absolute bottom-0 left-0 right-0 z-10 p-4 pointer-events-none">
        <div class="glass rounded-3xl p-4 max-w-2xl mx-auto pointer-events-auto shadow-2xl">
            <div id="fuelFilters" class="flex gap-2 overflow-x-auto pb-3 scrollbar-hide mb-3">
                {{-- Заполняется через JS --}}
            </div>

            <div class="grid grid-cols-4 gap-2">
                <button class="status-filter-btn has_fuel p-3 rounded-xl border flex flex-col items-center gap-1 transition" data-status="has_fuel">
                    <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
                    <span class="text-[10px] font-bold uppercase">Есть</span>
                </button>
                <button class="status-filter-btn queue p-3 rounded-xl border flex flex-col items-center gap-1 transition" data-status="queue">
                    <div class="w-3 h-3 rounded-full bg-orange-400"></div>
                    <span class="text-[10px] font-bold uppercase">Очередь</span>
                </button>
                <button class="status-filter-btn low_fuel p-3 rounded-xl border flex flex-col items-center gap-1 transition" data-status="low_fuel">
                    <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                    <span class="text-[10px] font-bold uppercase">Мало</span>
                </button>
                <button class="status-filter-btn no_fuel p-3 rounded-xl border flex flex-col items-center gap-1 transition" data-status="no_fuel">
                    <div class="w-3 h-3 rounded-full bg-red-400"></div>
                    <span class="text-[10px] font-bold uppercase">Нет</span>
                </button>
            </div>
        </div>
    </div>

    {{-- МОДАЛКА АЗС --}}
    <div id="stationModal" class="absolute inset-0 z-20 flex items-end sm:items-center justify-center p-4 bg-black/60 backdrop-blur-sm hidden">
        <div class="glass w-full max-w-md rounded-3xl p-6 shadow-2xl relative max-h-[90vh] overflow-y-auto">
            <button id="closeStationModal" class="absolute top-4 right-4 text-slate-400 hover:text-white transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            <div class="flex items-start gap-3 mb-4">
                <div id="stationBrandIcon" class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-lg flex-shrink-0 shadow-lg"></div>
                <div class="flex-1 min-w-0">
                    <h2 id="stationName" class="text-xl font-bold mb-1 truncate"></h2>
                    <p id="stationAddress" class="text-slate-400 text-sm truncate"></p>
                    <p class="text-slate-500 text-xs mt-1 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        </svg>
                        <span id="stationDistance"></span> км от вас
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3 mb-4 p-3 rounded-xl bg-slate-800/50">
                <span id="stationStatus" class="px-3 py-1 rounded-full text-xs font-bold uppercase flex items-center gap-2">
                    <span id="stationStatusDot" class="w-2 h-2 rounded-full"></span>
                    <span id="stationStatusText"></span>
                </span>
                <span id="stationUpdatedAt" class="text-xs text-slate-500 ml-auto">Обновлено: недавно</span>
            </div>

            <div id="queueInfoBlock" class="hidden mb-4 p-3 rounded-xl bg-orange-500/10 border border-orange-500/20">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-orange-500/20 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-xs text-orange-300 font-semibold uppercase tracking-wider">Размер очереди</p>
                        <p id="queueSizeText" class="text-sm text-orange-100 font-medium mt-0.5"></p>
                    </div>
                </div>
            </div>

            <div id="fuelTypesBlock" class="mb-4 hidden">
                <p class="text-xs text-slate-400 mb-2 uppercase tracking-wider font-semibold flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    Доступное топливо
                </p>
                <div id="fuelTypesList" class="grid grid-cols-2 gap-2"></div>
            </div>

            <div class="flex items-center gap-3 mb-4 p-3 rounded-xl bg-slate-800/30">
                <div class="flex items-center gap-2 text-xs text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span id="reportsCount">0 отметок за 8ч</span>
                </div>
                <div id="verifiedCountBlock" class="hidden flex items-center gap-2 text-xs text-emerald-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span id="verifiedCount">0 подтвержд.</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <a id="routeLink" href="#" target="_blank"
                   class="bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white py-3 rounded-xl font-semibold transition flex items-center justify-center gap-2 shadow-lg shadow-indigo-600/30">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                    </svg>
                    Маршрут
                </a>
                <button id="openReportBtn" class="bg-slate-700 hover:bg-slate-600 text-white py-3 rounded-xl font-semibold transition flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Отметить
                </button>
            </div>

            <button id="confirmReportBtn" class="hidden w-full mt-3 bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 py-2.5 rounded-xl text-sm font-medium transition flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"></path>
                </svg>
                Подтвердить информацию
            </button>
        </div>
    </div>

    {{-- МОДАЛКА ОТЧЕТА --}}
    <div id="reportModal" class="absolute inset-0 z-30 flex items-end sm:items-center justify-center p-4 bg-black/70 backdrop-blur-sm hidden">
        <div class="glass w-full max-w-md rounded-3xl p-6 shadow-2xl relative max-h-[90vh] overflow-y-auto">
            <button id="closeReportModal" class="absolute top-4 right-4 text-slate-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            <h3 class="text-xl font-bold mb-4">Создать отметку</h3>
            
            <form id="reportForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-2">Статус</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" class="status-btn p-3 rounded-xl transition flex items-center gap-2 border border-slate-600 hover:border-slate-500" data-status="has_fuel">
                            <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
                            <span class="text-sm">Есть топливо</span>
                        </button>
                        <button type="button" class="status-btn p-3 rounded-xl transition flex items-center gap-2 border border-slate-600 hover:border-slate-500" data-status="queue">
                            <div class="w-3 h-3 rounded-full bg-orange-400"></div>
                            <span class="text-sm">Очередь</span>
                        </button>
                        <button type="button" class="status-btn p-3 rounded-xl transition flex items-center gap-2 border border-slate-600 hover:border-slate-500" data-status="low_fuel">
                            <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                            <span class="text-sm">Мало топлива</span>
                        </button>
                        <button type="button" class="status-btn p-3 rounded-xl transition flex items-center gap-2 border border-slate-600 hover:border-slate-500" data-status="no_fuel">
                            <div class="w-3 h-3 rounded-full bg-red-400"></div>
                            <span class="text-sm">Нет топлива</span>
                        </button>
                    </div>
                </div>

                <div id="queueSizeField" class="hidden">
                    <label class="block text-sm font-medium text-slate-400 mb-2">Размер очереди</label>
                    <select id="queueSize" class="w-full bg-slate-800/50 border border-slate-600 rounded-xl px-4 py-2 text-white focus:outline-none focus:border-orange-500">
                        <option value="">Выберите размер...</option>
                        <option value="1-3 машины">1-3 машины</option>
                        <option value="5-10 машин">5-10 машин</option>
                        <option value="10+ машин">10+ машин</option>
                        <option value="Очень большая">Очень большая</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-2">Тип топлива <span class="text-red-400">*</span></label>
                    <div id="fuelTypesContainer" class="flex flex-wrap gap-2"></div>
                </div>

                <button type="submit" id="submitReportBtn" disabled
                        class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 disabled:opacity-50 text-white py-3 rounded-xl font-semibold transition">
                    Отправить отметку
                </button>
            </form>
        </div>
    </div>

    {{-- УВЕДОМЛЕНИЯ --}}
    <div id="notifications" class="fixed bottom-4 right-4 z-50 space-y-2 max-w-sm"></div>

</div>

{{-- ============================================= --}}
{{-- СКРИПТЫ (в самом конце, после HTML)            --}}
{{-- ============================================= --}}

{{-- 1. CSRF токен для AJAX-запросов --}}
<meta name="csrf-token" content="{{ csrf_token() }}">

{{-- 2. Данные из Laravel (ДО подключения JS) --}}
<script>
    window.fuelRadarData = {
        fuels: @json($fuelTypes)
    };
</script>

{{-- 3. Яндекс.Карты --}}
<script src="https://api-maps.yandex.ru/2.1/?apikey={{ config('services.yandex_maps.key') }}&lang=ru_RU" type="text/javascript"></script>

{{-- 4. Основная логика приложения --}}
<script src="{{ asset('js/fuel-radar.js') }}?v={{ filemtime(public_path('js/fuel-radar.js')) }}"></script>

@endsection