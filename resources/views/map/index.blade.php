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
            {{-- Переключатель темы --}}
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
        </div>
    </div>

    {{-- НИЖНЯЯ ПАНЕЛЬ --}}
    <div class="absolute bottom-0 left-0 right-0 z-10 p-4 pointer-events-none">
        <div class="glass rounded-3xl p-4 max-w-2xl mx-auto pointer-events-auto shadow-2xl">
            <div id="fuelFilters" class="flex gap-2 overflow-x-auto pb-3 scrollbar-hide mb-3">
                <!-- Заполняется через JS -->
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
        <div class="glass w-full max-w-md rounded-3xl p-6 shadow-2xl relative">
            <button id="closeStationModal" class="absolute top-4 right-4 text-slate-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            <div class="flex items-start gap-3 mb-4">
                <div id="stationBrandIcon" class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-lg flex-shrink-0 shadow-lg"></div>
                <div class="flex-1">
                    <h2 id="stationName" class="text-2xl font-bold mb-1"></h2>
                    <p id="stationAddress" class="text-slate-400 text-sm"></p>
                    <p class="text-slate-500 text-xs mt-1"><span id="stationDistance"></span> км от вас</p>
                </div>
            </div>

            <div class="flex items-center gap-3 mb-6 p-3 rounded-xl bg-slate-800/50">
                <span id="stationStatus" class="px-3 py-1 rounded-full text-xs font-bold uppercase"></span>
                <span class="text-xs text-slate-500">Обновлено: недавно</span>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <a id="routeLink" href="#" target="_blank"
                   class="bg-indigo-600 hover:bg-indigo-500 text-white py-3 rounded-xl font-semibold transition flex items-center justify-center gap-2">
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
            {{-- СТАТУС --}}
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

            {{-- РАЗМЕР ОЧЕРЕДИ (появляется только при статусе "Очередь") --}}
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

            {{-- ТИП ТОПЛИВА --}}
            <div>
                <label class="block text-sm font-medium text-slate-400 mb-2">Тип топлива <span class="text-red-400">*</span></label>
                <div id="fuelTypesContainer" class="flex flex-wrap gap-2">
                    {{-- Заполняется через JS --}}
                </div>
            </div>

            <button type="submit" id="submitReportBtn" disabled
                    class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 disabled:opacity-50 text-white py-3 rounded-xl font-semibold transition">
                Отправить отметку
            </button>
        </form>
    </div>
</div>

    {{-- УВЕДОМЛЕНИЯ --}}
    <div id="notifications" class="fixed top-4 right-4 z-50 space-y-2"></div>

</div>

<script>
// Данные из Laravel
const fuelRadarData = { fuels: @json($fuelTypes) };

// Состояние приложения
const state = {
    map: null,
    clusterer: null,
    placemarks: [],
    stations: [],
    selectedStation: null,
    selectedFuels: [],      // Выбранные типы топлива
    filterStatus: 'all',    // Фильтр по статусу
    userLocation: null,
    userPlacemark: null,
    isDarkTheme: !document.documentElement.classList.contains('light'),
    reportForm: { status: 'has_fuel', fuel_types: [] }
};

// Инициализация
document.addEventListener('DOMContentLoaded', () => {
    initFuelFilters();
    initFuelTypes();
    initEventListeners();
    
    ymaps.ready(() => {
        console.log('✅ Яндекс.Карты загружены');
        initMap();
    });
});

function initFuelFilters() {
    const container = document.getElementById('fuelFilters');
    fuelRadarData.fuels.forEach(fuel => {
        const btn = document.createElement('button');
        btn.className = 'px-4 py-2 rounded-full text-sm font-medium border whitespace-nowrap transition bg-slate-800/50 text-slate-300 border-slate-700 hover:bg-slate-700/50';
        btn.textContent = fuel.name;
        btn.dataset.fuelId = fuel.id;
        btn.addEventListener('click', () => toggleFuel(fuel.id, btn));
        container.appendChild(btn);
    });
}

function initFuelTypes() {
    const container = document.getElementById('fuelTypesContainer');
    fuelRadarData.fuels.forEach(fuel => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'fuel-type-btn px-4 py-2 rounded-lg border text-sm transition bg-slate-700 text-slate-300 border-slate-600 hover:bg-slate-600';
        btn.textContent = fuel.name;
        btn.dataset.fuelShortName = fuel.short_name;
        btn.addEventListener('click', () => toggleReportFuel(fuel.short_name, btn));
        container.appendChild(btn);
    });
}

async function searchCity(query) {
    try {
        const response = await fetch(
            `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5&countrycodes=ru`
        );
        const data = await response.json();
        
        const searchResults = document.getElementById('searchResults');
        searchResults.innerHTML = '';
        
        if (data.length > 0) {
            data.forEach(item => {
                const div = document.createElement('div');
                div.className = 'search-result-item';
                div.innerHTML = `
                    <div class="text-sm font-medium">${item.display_name.split(',')[0]}</div>
                    <div class="text-xs text-slate-400 truncate">${item.display_name}</div>
                `;
                div.addEventListener('click', () => {
                    const lat = parseFloat(item.lat);
                    const lon = parseFloat(item.lon);
                    if (state.map) {
                        state.map.setCenter([lat, lon], 13, { duration: 500 });
                        setTimeout(() => loadStations(), 1000);
                        showNotification(`Переход: ${item.display_name.split(',')[0]}`, 'success');
                    }
                    searchResults.classList.add('hidden');
                    document.getElementById('searchInput').value = '';
                    document.getElementById('clearSearch').classList.add('hidden');
                });
                searchResults.appendChild(div);
            });
            searchResults.classList.remove('hidden');
        } else {
            searchResults.classList.add('hidden');
        }
    } catch (error) {
        console.error('Search error:', error);
        document.getElementById('searchResults').classList.add('hidden');
    }
}



function initEventListeners() {
    const searchInput = document.getElementById('searchInput');
    const clearSearch = document.getElementById('clearSearch');
    const searchResults = document.getElementById('searchResults');
    
    let searchTimeout;
    
    // Поиск через Nominatim (OpenStreetMap)
    searchInput.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        const query = e.target.value;
        
        if (query.length < 2) {
            searchResults.classList.add('hidden');
            clearSearch.classList.add('hidden');
            return;
        }
        
        clearSearch.classList.remove('hidden');
        searchTimeout = setTimeout(() => searchCity(query), 300);
    });
    
    clearSearch.addEventListener('click', () => {
        searchInput.value = '';
        searchResults.classList.add('hidden');
        clearSearch.classList.add('hidden');
    });
    
    // Закрытие результатов при клике вне поиска
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.search-container')) {
            searchResults.classList.add('hidden');
        }
    });
    
    // Остальные обработчики
    document.getElementById('themeToggle').addEventListener('click', toggleTheme);
    document.getElementById('findBestBtn').addEventListener('click', findBestStation);
    document.getElementById('myLocationBtn').addEventListener('click', getUserLocation);
    
    document.getElementById('closeStationModal').addEventListener('click', () => {
        document.getElementById('stationModal').classList.add('hidden');
        state.selectedStation = null;
    });
    
    document.getElementById('stationModal').addEventListener('click', (e) => {
        if (e.target.id === 'stationModal') {
            document.getElementById('stationModal').classList.add('hidden');
            state.selectedStation = null;
        }
    });
    
    document.getElementById('closeReportModal').addEventListener('click', () => {
        document.getElementById('reportModal').classList.add('hidden');
    });
    
    document.getElementById('reportModal').addEventListener('click', (e) => {
        if (e.target.id === 'reportModal') {
            document.getElementById('reportModal').classList.add('hidden');
        }
    });
    
    document.getElementById('openReportBtn').addEventListener('click', openReportModal);
    
    document.getElementById('reportForm').addEventListener('submit', submitReport);
    
    // Фильтры статусов
    document.querySelectorAll('.status-filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const status = btn.dataset.status;
            
            if (state.filterStatus === status) {
                state.filterStatus = 'all';
                btn.classList.remove('active');
            } else {
                state.filterStatus = status;
                document.querySelectorAll('.status-filter-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            }
            
            loadStations();
        });
    });
    
    // Кнопки статуса в форме отчёта
    document.querySelectorAll('.status-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.status-btn').forEach(b => {
                b.className = 'status-btn p-3 rounded-xl transition flex items-center gap-2 border border-slate-600 hover:border-slate-500';
            });
            
            const status = btn.dataset.status;
            
            if (status === 'has_fuel') {
                btn.className = 'status-btn p-3 rounded-xl transition flex items-center gap-2 border-2 border-emerald-500 bg-emerald-500/20';
                document.getElementById('queueSizeField').classList.add('hidden');
            } else if (status === 'queue') {
                btn.className = 'status-btn p-3 rounded-xl transition flex items-center gap-2 border-2 border-orange-500 bg-orange-500/20';
                document.getElementById('queueSizeField').classList.remove('hidden');
            } else if (status === 'low_fuel') {
                btn.className = 'status-btn p-3 rounded-xl transition flex items-center gap-2 border-2 border-yellow-500 bg-yellow-500/20';
                document.getElementById('queueSizeField').classList.add('hidden');
            } else if (status === 'no_fuel') {
                btn.className = 'status-btn p-3 rounded-xl transition flex items-center gap-2 border-2 border-red-500 bg-red-500/20';
                document.getElementById('queueSizeField').classList.add('hidden');
            }
            
            state.reportForm.status = status;
        });
    });
}

// ============================================
// КАРТА С КЛАСТЕРИЗАЦИЕЙ
// ============================================
function initMap() {
    state.map = new ymaps.Map('map', {
        center: [53.7596, 87.1216],
        zoom: 13,
        controls: []
    });
    
    // Добавляем зум
    state.map.controls.add('zoomControl', {
        options: {
            position: { right: 20, bottom: 260 }
        }
    });
    
    // Создаём кластеризатор
    state.clusterer = new ymaps.Clusterer({
        preset: 'islands#invertedVioletClusterIcons',
        groupByCoordinates: false,
        clusterDisableClickZoom: true,
        clusterOpenBalloonOnClick: false,
        gridSize: 64,
        maxZoom: 15
    });
    
    state.map.geoObjects.add(state.clusterer);
    
    state.map.events.add('click', () => {
        document.getElementById('stationModal').classList.add('hidden');
        state.selectedStation = null;
    });
    
    let moveTimeout;
    state.map.events.add('boundschange', () => {
        clearTimeout(moveTimeout);
        moveTimeout = setTimeout(() => loadStations(), 500);
    });
    
    loadStations();
    getUserLocation();
    setInterval(() => loadStations(), 120000);
}

// ============================================
// ТЕМА
// ============================================
function toggleTheme() {
    state.isDarkTheme = !state.isDarkTheme;
    const circle = document.getElementById('themeToggleCircle');
    const toggle = document.getElementById('themeToggle');
    
    if (state.isDarkTheme) {
        document.documentElement.classList.remove('light');
        localStorage.setItem('theme', 'dark');
        toggle.className = 'relative w-14 h-7 rounded-full transition-colors duration-300 bg-slate-700';
        circle.className = 'absolute top-1 left-1 w-5 h-5 rounded-full bg-white shadow-md transition-transform duration-300 flex items-center justify-center text-xs';
        circle.textContent = '🌙';
    } else {
        document.documentElement.classList.add('light');
        localStorage.setItem('theme', 'light');
        toggle.className = 'relative w-14 h-7 rounded-full transition-colors duration-300 bg-yellow-400';
        circle.className = 'absolute top-1 left-1 w-5 h-5 rounded-full bg-white shadow-md transition-transform duration-300 flex items-center justify-center text-xs translate-x-7';
        circle.textContent = '☀️';
    }
}

// ============================================
// СОЗДАНИЕ ИКОНКИ КЛАСТЕРА
// ============================================
function createClusterIcon(count, dominantStatus) {
    const canvas = document.createElement('canvas');
    canvas.width = 48;
    canvas.height = 48;
    const ctx = canvas.getContext('2d');
    
    // Тёмный фон кластера (как на скриншоте)
    ctx.beginPath();
    ctx.arc(24, 24, 22, 0, Math.PI * 2);
    ctx.fillStyle = '#1f2937'; // Тёмно-серый
    ctx.fill();
    
    // Белая обводка
    ctx.strokeStyle = '#ffffff';
    ctx.lineWidth = 2;
    ctx.stroke();
    
    // Цифра белая
    ctx.fillStyle = '#ffffff';
    ctx.font = 'bold 20px Inter, Arial, sans-serif';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(count.toString(), 24, 24);
    
    return canvas.toDataURL('image/png');
}

// ============================================
// СОЗДАНИЕ ИКОНКИ МЕТКИ (компактная)
// ============================================
function createStationIcon(station) {
    const canvas = document.createElement('canvas');
    canvas.width = 40;
    canvas.height = 50;
    const ctx = canvas.getContext('2d');
    
    const brandColor = station.brand_color || '#6366f1';
    const statusColor = getStatusColor(station.status);
    const letter = getBrandLetter(station.brand_name);
    
    // Тень
    ctx.shadowColor = 'rgba(0, 0, 0, 0.2)';
    ctx.shadowBlur = 8;
    ctx.shadowOffsetY = 3;
    
    // Серый круг (фон как на скриншоте)
    ctx.beginPath();
    ctx.arc(20, 20, 18, 0, Math.PI * 2);
    ctx.fillStyle = '#9ca3af'; // Серый цвет
    ctx.fill();
    ctx.strokeStyle = '#ffffff';
    ctx.lineWidth = 2;
    ctx.stroke();
    
    // Хвост капли (серый)
    ctx.beginPath();
    ctx.moveTo(12, 30);
    ctx.lineTo(20, 44);
    ctx.lineTo(28, 30);
    ctx.closePath();
    ctx.fillStyle = '#9ca3af';
    ctx.fill();
    ctx.strokeStyle = '#ffffff';
    ctx.lineWidth = 2;
    ctx.stroke();
    
    // Перекрываем соединение
    ctx.beginPath();
    ctx.arc(20, 20, 17, 0, Math.PI * 2);
    ctx.fillStyle = '#9ca3af';
    ctx.fill();
    
    ctx.shadowBlur = 0;
    
    // Буква бренда (белая)
    ctx.fillStyle = '#ffffff';
    ctx.font = 'bold 16px Inter, Arial, sans-serif';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(letter, 20, 20);
    
    // Статусная точка (цветная, справа внизу)
    ctx.beginPath();
    ctx.arc(32, 32, 6, 0, Math.PI * 2);
    ctx.fillStyle = statusColor;
    ctx.fill();
    ctx.strokeStyle = '#ffffff';
    ctx.lineWidth = 2;
    ctx.stroke();
    
    return canvas.toDataURL('image/png');
}

// ============================================
// ИКОНКА ДЛЯ ОСОБЫХ МЕТОК (например, мечеть)
// ============================================
function createSpecialIcon(iconType, color) {
    const canvas = document.createElement('canvas');
    canvas.width = 40;
    canvas.height = 50;
    const ctx = canvas.getContext('2d');
    
    // Тень
    ctx.shadowColor = 'rgba(0, 0, 0, 0.2)';
    ctx.shadowBlur = 8;
    ctx.shadowOffsetY = 3;
    
    // Красный круг (для особых меток)
    ctx.beginPath();
    ctx.arc(20, 20, 18, 0, Math.PI * 2);
    ctx.fillStyle = color || '#ef4444';
    ctx.fill();
    ctx.strokeStyle = '#ffffff';
    ctx.lineWidth = 2;
    ctx.stroke();
    
    // Хвост
    ctx.beginPath();
    ctx.moveTo(12, 30);
    ctx.lineTo(20, 44);
    ctx.lineTo(28, 30);
    ctx.closePath();
    ctx.fillStyle = color || '#ef4444';
    ctx.fill();
    ctx.strokeStyle = '#ffffff';
    ctx.lineWidth = 2;
    ctx.stroke();
    
    // Перекрываем соединение
    ctx.beginPath();
    ctx.arc(20, 20, 17, 0, Math.PI * 2);
    ctx.fillStyle = color || '#ef4444';
    ctx.fill();
    
    ctx.shadowBlur = 0;
    
    // Иконка (например, мечеть)
    ctx.fillStyle = '#ffffff';
    ctx.font = 'bold 18px Arial, sans-serif';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(iconType || '🕌', 20, 20);
    
    return canvas.toDataURL('image/png');
}

// ============================================
// ИКОНКА ПОЛЬЗОВАТЕЛЯ
// ============================================
function createUserIcon() {
    const canvas = document.createElement('canvas');
    canvas.width = 32;
    canvas.height = 32;
    const ctx = canvas.getContext('2d');
    
    // Синяя точка
    ctx.beginPath();
    ctx.arc(16, 16, 12, 0, Math.PI * 2);
    ctx.fillStyle = '#3b82f6';
    ctx.fill();
    ctx.strokeStyle = '#ffffff';
    ctx.lineWidth = 3;
    ctx.stroke();
    
    // Белая точка в центре
    ctx.beginPath();
    ctx.arc(16, 16, 5, 0, Math.PI * 2);
    ctx.fillStyle = '#ffffff';
    ctx.fill();
    
    return canvas.toDataURL('image/png');
}


// ============================================
// СОЗДАНИЕ МЕТКИ
// ============================================
// ============================================
// СОЗДАНИЕ МЕТКИ (стандартная Яндекс)
// ============================================
function createPlacemark(station) {
    // Цвет метки в зависимости от статуса
    const statusColors = {
        'has_fuel': 'green',      // Зелёный - есть топливо
        'queue': 'orange',        // Оранжевый - очередь
        'low_fuel': 'yellow',     // Жёлтый - мало топлива
        'no_fuel': 'red',         // Красный - нет топлива
        'unknown': 'gray'         // Серый - неизвестно
    };
    
    const iconColor = statusColors[station.status] || 'gray';
    
    const placemark = new ymaps.Placemark(
        [station.lat, station.lng],
        {
            hintContent: `${station.name} — ${getStatusText(station.status)}`,
            balloonContent: `
                <div style="padding: 10px; font-family: Arial, sans-serif; min-width: 200px;">
                    <h3 style="margin: 0 0 8px 0; font-size: 16px; font-weight: bold;">${station.name}</h3>
                    <p style="margin: 0 0 5px 0; font-size: 13px; color: #666;">${station.address}</p>
                    <p style="margin: 0 0 10px 0; font-size: 12px; color: #999;">${station.distance} км</p>
                    <p style="margin: 0; font-weight: bold; color: ${getStatusColor(station.status)};">
                        ${getStatusText(station.status)}
                    </p>
                </div>
            `
        },
        {
            // Стандартный пресет Яндекс.Карт с цветом
            preset: 'islands#' + iconColor + 'Icon'
        }
    );
    
    placemark.events.add('click', (e) => {
        e.get('target').balloon.close();
        showStationModal(station);
    });
    
    return placemark;
}

// ============================================
// ПОКАЗ МОДАЛКИ АЗС
// ============================================
function showStationModal(station) {
    state.selectedStation = station;
    
    document.getElementById('stationBrandIcon').style.background = station.brand_color || '#6366f1';
    document.getElementById('stationBrandIcon').textContent = getBrandLetter(station.brand_name);
    document.getElementById('stationName').textContent = station.name;
    document.getElementById('stationAddress').textContent = station.address;
    document.getElementById('stationDistance').textContent = station.distance;
    
    const statusEl = document.getElementById('stationStatus');
    statusEl.textContent = getStatusText(station.status);
    statusEl.className = 'px-3 py-1 rounded-full text-xs font-bold uppercase ' + getStatusClass(station.status);
    
    const center = state.map.getCenter();
    const userLat = state.userLocation?.lat || center[0];
    const userLng = state.userLocation?.lng || center[1];
    document.getElementById('routeLink').href = `https://yandex.ru/maps/?rtext=${userLat},${userLng}&rtt=auto&pt=${station.lat},${station.lng}`;
    
    document.getElementById('stationModal').classList.remove('hidden');
}

function getStatusClass(status) {
    const classes = {
        'has_fuel': 'bg-emerald-500/20 text-emerald-400',
        'queue': 'bg-orange-500/20 text-orange-400',
        'low_fuel': 'bg-yellow-500/20 text-yellow-400',
        'no_fuel': 'bg-red-500/20 text-red-400',
        'unknown': 'bg-slate-700 text-slate-400'
    };
    return classes[status] || classes['unknown'];
}

function getBrandLetter(brandName) {
    if (!brandName) return '?';
    return brandName.charAt(0).toUpperCase();
}

function getStatusColor(status) {
    const colors = {
        'has_fuel': '#10b981', 'queue': '#f59e0b',
        'low_fuel': '#eab308', 'no_fuel': '#ef4444', 'unknown': '#64748b'
    };
    return colors[status] || '#64748b';
}

function getStatusText(status) {
    const texts = {
        'has_fuel': 'Есть топливо', 'queue': 'Очередь',
        'low_fuel': 'Мало топлива', 'no_fuel': 'Нет топлива', 'unknown': 'Нет данных'
    };
    return texts[status] || 'Неизвестно';
}

// ============================================
// ЗАГРУЗКА СТАНЦИЙ С КЛАСТЕРИЗАЦИЕЙ
// ============================================
async function loadStations() {
    if (!state.map) return;
    try {
        const center = state.map.getCenter();
        const params = new URLSearchParams({
            lat: center[0], 
            lng: center[1], 
            radius: 15
        });
        
        // Фильтр по статусу
        if (state.filterStatus !== 'all') {
            params.append('status', state.filterStatus);
        }
        
        // Фильтр по типу топлива (берём первый выбранный)
        if (state.selectedFuels.length > 0) {
            // Находим short_name выбранного топлива
            const fuel = fuelRadarData.fuels.find(f => f.id === state.selectedFuels[0]);
            if (fuel) {
                params.append('fuel_type', fuel.short_name);
            }
        }
        
        const response = await fetch('/api/stations?' + params.toString(), {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        if (!response.ok) throw new Error('Failed to load stations');
        state.stations = await response.json();
        console.log('✅ Загружено станций:', state.stations.length);
        renderPlacemarks();
    } catch (error) {
        console.error('❌ Error loading stations:', error);
    }
}

function renderPlacemarks() {
    if (!state.clusterer) return;
    
    // Удаляем старые метки из кластеризатора
    state.clusterer.removeAll();
    state.placemarks = [];
    
    // Определяем доминирующий статус для кластеров
    const statusCounts = {
        'has_fuel': 0, 'queue': 0, 'low_fuel': 0, 'no_fuel': 0
    };
    
    state.stations.forEach(station => {
        try {
            const placemark = createPlacemark(station);
            state.clusterer.add(placemark);
            state.placemarks.push(placemark);
            
            if (station.status in statusCounts) {
                statusCounts[station.status]++;
            }
        } catch (error) {
            console.error('Ошибка создания метки:', error, station);
        }
    });
    
    console.log('✅ Отрисовано меток:', state.placemarks.length);
}

// ============================================
// ФИЛЬТРЫ
// ============================================
function toggleFuel(fuelId, btn) {
    const index = state.selectedFuels.indexOf(fuelId);
    
    if (index > -1) {
        // Убираем фильтр
        state.selectedFuels.splice(index, 1);
        btn.className = 'px-4 py-2 rounded-full text-sm font-medium border whitespace-nowrap transition bg-slate-800/50 text-slate-300 border-slate-700 hover:bg-slate-700/50';
    } else {
        // Добавляем фильтр (только один тип топлива)
        state.selectedFuels = [fuelId];
        btn.className = 'px-4 py-2 rounded-full text-sm font-medium border whitespace-nowrap transition bg-indigo-500 text-white border-indigo-400';
        
        // Сбрасываем другие кнопки
        document.querySelectorAll('#fuelFilters button').forEach(b => {
            if (parseInt(b.dataset.fuelId) !== fuelId) {
                b.className = 'px-4 py-2 rounded-full text-sm font-medium border whitespace-nowrap transition bg-slate-800/50 text-slate-300 border-slate-700 hover:bg-slate-700/50';
            }
        });
    }
    
    // Перезагружаем станции с новым фильтром
    loadStations();
}

function toggleReportFuel(fuelShortName, btn) {
    const index = state.reportForm.fuel_types.indexOf(fuelShortName);
    if (index > -1) {
        state.reportForm.fuel_types.splice(index, 1);
        btn.className = 'fuel-type-btn px-4 py-2 rounded-lg border text-sm transition bg-slate-700 text-slate-300 border-slate-600 hover:bg-slate-600';
    } else {
        state.reportForm.fuel_types.push(fuelShortName);
        btn.className = 'fuel-type-btn px-4 py-2 rounded-lg border text-sm transition bg-indigo-500 text-white border-indigo-400';
    }
    updateSubmitButton();
}

function updateSubmitButton() {
    const btn = document.getElementById('submitReportBtn');
    btn.disabled = state.reportForm.fuel_types.length === 0;
}

function openReportModal() {
    if (!state.selectedStation) {
        showNotification('Выберите АЗС на карте', 'info');
        return;
    }
    document.getElementById('reportModal').classList.remove('hidden');
}

async function submitReport(e) {
    e.preventDefault();
    if (state.reportForm.fuel_types.length === 0) {
        showNotification('Выберите топливо', 'error');
        return;
    }
    
    // Если статус "Очередь", проверяем выбран ли размер
    if (state.reportForm.status === 'queue') {
        const queueSize = document.getElementById('queueSize').value;
        if (!queueSize) {
            showNotification('Выберите размер очереди', 'error');
            return;
        }
        state.reportForm.queue_size = queueSize;
    }
    
    const btn = document.getElementById('submitReportBtn');
    btn.disabled = true;
    btn.textContent = 'Отправка...';
    
    try {
        const center = state.map ? state.map.getCenter() : [0, 0];
        const response = await fetch(`/api/stations/${state.selectedStation.id}/report`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                status: state.reportForm.status,
                fuel_types: state.reportForm.fuel_types,
                queue_size: state.reportForm.queue_size || null,
                lat: state.userLocation?.lat || center[0],
                lng: state.userLocation?.lng || center[1],
            })
        });
        
        if (!response.ok) throw new Error('Failed');
        showNotification('Спасибо!', 'success');
        document.getElementById('reportModal').classList.add('hidden');
        // Сбрасываем форму
        document.getElementById('queueSizeField').classList.add('hidden');
        document.getElementById('queueSize').value = '';
        loadStations();
    } catch (error) {
        showNotification(error.message, 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Отправить отметку';
    }
}

function findBestStation() {
    if (!state.map) {
        showNotification('Карта не загружена', 'error');
        return;
    }
    if (state.stations.length === 0) {
        showNotification('Нет заправок', 'info');
        return;
    }
    const best = state.stations
        .filter(s => s.status === 'has_fuel')
        .sort((a, b) => (b.verified_count || 0) - (a.verified_count || 0))[0];
    if (best) {
        showStationModal(best);
        state.map.setCenter([best.lat, best.lng], 16, { duration: 500 });
    } else {
        showNotification('Нет заправок с топливом', 'info');
    }
}

function getUserLocation() {
    if (!navigator.geolocation) {
        showNotification('Геолокация не поддерживается', 'error');
        return;
    }
    navigator.geolocation.getCurrentPosition(
        (position) => {
            state.userLocation = {
                lat: position.coords.latitude,
                lng: position.coords.longitude
            };
            if (state.map) {
                state.map.setCenter([state.userLocation.lat, state.userLocation.lng], 13, { duration: 500 });
            }
            if (state.userPlacemark && state.map) {
                state.map.geoObjects.remove(state.userPlacemark);
            }
            
            const userIconUrl = createUserIcon();
            
            state.userPlacemark = new ymaps.Placemark(
                [state.userLocation.lat, state.userLocation.lng],
                { hintContent: '📍 Вы здесь' },
                {
                    iconLayout: 'default#image',
                    iconImageHref: userIconUrl,
                    iconImageSize: [24, 24],
                    iconImageOffset: [-12, -12]
                }
            );
            
            if (state.map) state.map.geoObjects.add(state.userPlacemark);
            showNotification('Местоположение определено', 'success');
            setTimeout(() => loadStations(), 1000);
        },
        (error) => {
            console.error('Geolocation error:', error);
            showNotification('Не удалось определить местоположение', 'error');
        }
    );
}

function showNotification(message, type = 'info') {
    const container = document.getElementById('notifications');
    const div = document.createElement('div');
    div.className = `text-white px-6 py-3 rounded-xl shadow-xl ${
        type === 'success' ? 'bg-emerald-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500'
    }`;
    div.textContent = message;
    container.appendChild(div);
    
    setTimeout(() => {
        div.style.opacity = '0';
        div.style.transition = 'opacity 0.3s';
        setTimeout(() => div.remove(), 300);
    }, 3000);
}
</script>
@endsection