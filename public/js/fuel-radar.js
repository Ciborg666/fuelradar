// public/js/fuel-radar.js

// Состояние приложения
const state = {
    map: null,
    clusterer: null,
    placemarks: [],
    stations: [],
    selectedStation: null,
    selectedFuels: [],
    filterStatus: 'all',
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

// ===== ВСЕ ОСТАЛЬНЫЕ ФУНКЦИИ ИЗ ВАШЕГО СКРИПТА =====

function initFuelFilters() {
    const container = document.getElementById('fuelFilters');
    window.fuelRadarData.fuels.forEach(fuel => {
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
    window.fuelRadarData.fuels.forEach(fuel => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'fuel-type-btn px-4 py-2 rounded-lg border text-sm transition bg-slate-700 text-slate-300 border-slate-600 hover:bg-slate-600';
        btn.textContent = fuel.name;
        btn.dataset.fuelShortName = fuel.short_name;
        btn.addEventListener('click', () => toggleReportFuel(fuel.short_name, btn));
        container.appendChild(btn);
    });
}

function initEventListeners() {
    const searchInput = document.getElementById('searchInput');
    const clearSearch = document.getElementById('clearSearch');
    const searchResults = document.getElementById('searchResults');
    
    let searchTimeout;
    
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
    
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.search-container')) {
            searchResults.classList.add('hidden');
        }
    });
    
    document.getElementById('themeToggle').addEventListener('click', toggleTheme);
    document.getElementById('findBestBtn').addEventListener('click', findBestStation);
    document.getElementById('myLocationBtn').addEventListener('click', () => {
        getUserLocation(true);
    });
    
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

    // Обработчик для кнопки "Разрешить геолокацию"
    const requestLocationBtn = document.getElementById('requestLocationBtn');
    if (requestLocationBtn) {
        requestLocationBtn.addEventListener('click', () => {
            getUserLocation(true);
            requestLocationBtn.classList.add('hidden');
        });
    }
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

function initMap() {
    state.map = new ymaps.Map('map', {
        center: [53.7596, 87.1216],
        zoom: 13,
        controls: []
    });
    
    state.map.controls.add('zoomControl', {
        options: { position: { right: 20, bottom: 260 } }
    });
    
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
    // Автоматически запрашиваем геолокацию только если нет сохранённой
    const savedLocation = localStorage.getItem('userLocation');
    if (!savedLocation) {
        setTimeout(() => getUserLocation(true), 1000);
    } else {
        getUserLocation(false); // Тихо загружаем из кэша
    }
    setInterval(() => loadStations(), 120000);
}

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

function createPlacemark(station) {
    const statusColors = {
        'has_fuel': 'green',
        'queue': 'orange',
        'low_fuel': 'yellow',
        'no_fuel': 'red',
        'unknown': 'gray'
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
        { preset: 'islands#' + iconColor + 'Icon' }
    );
    
    placemark.events.add('click', (e) => {
        e.get('target').balloon.close();
        showStationModal(station);
    });
    
    return placemark;
}

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
    
    // === РАЗМЕР ОЧЕРЕДИ ===
    const queueBlock = document.getElementById('queueInfoBlock');
    if (station.status === 'queue' && station.queue_size) {
        document.getElementById('queueSizeText').textContent = station.queue_size;
        queueBlock.classList.remove('hidden');
    } else {
        queueBlock.classList.add('hidden');
    }
    
    // === ТИПЫ ТОПЛИВА ===
    const fuelBlock = document.getElementById('fuelTypesBlock');
    const fuelList = document.getElementById('fuelTypesList');
    fuelList.innerHTML = '';
    
    if (station.fuel_types && station.fuel_types.length > 0) {
        station.fuel_types.forEach(fuelShort => {
            const fuel = window.fuelRadarData.fuels.find(f => f.short_name === fuelShort);
            const fuelName = fuel ? fuel.name : fuelShort;
            
            const item = document.createElement('div');
            item.className = 'flex items-center gap-2 p-2.5 rounded-lg bg-indigo-500/10 border border-indigo-500/20';
            item.innerHTML = `
                <div class="w-8 h-8 rounded-lg bg-indigo-500/20 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <span class="text-sm font-medium text-indigo-200">${fuelName}</span>
            `;
            fuelList.appendChild(item);
        });
        fuelBlock.classList.remove('hidden');
    } else {
        fuelBlock.classList.add('hidden');
    }
    
    // === СЧЁТЧИКИ ===
    const reportsCount = station.reports_count || 0;
    document.getElementById('reportsCount').textContent = reportsCount + ' ' + getDeclension(reportsCount, ['отметка', 'отметки', 'отметок']) + ' за 8ч';
    
    const verifiedCount = station.verified_count || 0;
    const verifiedBlock = document.getElementById('verifiedCountBlock');
    if (verifiedCount > 0) {
        document.getElementById('verifiedCount').textContent = verifiedCount + ' подтвержд.';
        verifiedBlock.classList.remove('hidden');
    } else {
        verifiedBlock.classList.add('hidden');
    }
    
    const center = state.map.getCenter();
    const userLat = state.userLocation?.lat || center[0];
    const userLng = state.userLocation?.lng || center[1];
    document.getElementById('routeLink').href = `https://yandex.ru/maps/?rtext=${userLat},${userLng}&rtt=auto&pt=${station.lat},${station.lng}`;
    
    document.getElementById('stationModal').classList.remove('hidden');

    // === КНОПКА ПОДТВЕРЖДЕНИЯ ===
const confirmBtn = document.getElementById('confirmReportBtn');

console.log('🔘 Проверка кнопки подтверждения:', {
    status: station.status,
    hasStatus: !!station.status,
    isNotUnknown: station.status !== 'unknown'
});

if (station.status && station.status !== 'unknown') {
    confirmBtn.classList.remove('hidden');
    confirmBtn.onclick = () => voteForReport(station);
    console.log('✅ Кнопка показана');
} else {
    confirmBtn.classList.add('hidden');
    console.log('❌ Кнопка скрыта (нет статуса или unknown)');
}
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

function getDeclension(number, titles) {
    const cases = [2, 0, 1, 1, 1, 2];
    return titles[(number % 100 > 4 && number % 100 < 20) ? 2 : cases[(number % 10 < 5) ? number % 10 : 5]];
}

async function loadStations() {
    if (!state.map) return;
    try {
        const center = state.map.getCenter();
        const params = new URLSearchParams({
            lat: center[0], 
            lng: center[1], 
            radius: 15
        });
        
        if (state.filterStatus !== 'all') {
            params.append('status', state.filterStatus);
        }
        
        if (state.selectedFuels.length > 0) {
            const fuel = window.fuelRadarData.fuels.find(f => f.id === state.selectedFuels[0]);
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
    
    state.clusterer.removeAll();
    state.placemarks = [];
    
    state.stations.forEach(station => {
        try {
            const placemark = createPlacemark(station);
            state.clusterer.add(placemark);
            state.placemarks.push(placemark);
        } catch (error) {
            console.error('Ошибка создания метки:', error, station);
        }
    });
}

function toggleFuel(fuelId, btn) {
    const index = state.selectedFuels.indexOf(fuelId);
    
    if (index > -1) {
        state.selectedFuels.splice(index, 1);
        btn.className = 'px-4 py-2 rounded-full text-sm font-medium border whitespace-nowrap transition bg-slate-800/50 text-slate-300 border-slate-700 hover:bg-slate-700/50';
    } else {
        state.selectedFuels = [fuelId];
        btn.className = 'px-4 py-2 rounded-full text-sm font-medium border whitespace-nowrap transition bg-indigo-500 text-white border-indigo-400';
        
        document.querySelectorAll('#fuelFilters button').forEach(b => {
            if (parseInt(b.dataset.fuelId) !== fuelId) {
                b.className = 'px-4 py-2 rounded-full text-sm font-medium border whitespace-nowrap transition bg-slate-800/50 text-slate-300 border-slate-700 hover:bg-slate-700/50';
            }
        });
    }
    
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

// ============================================
// ГЕОЛОКАЦИЯ ПОЛЬЗОВАТЕЛЯ
// ============================================
// ============================================
// ГЕОЛОКАЦИЯ ПОЛЬЗОВАТЕЛЯ
// ============================================
function getUserLocation(shouldNotify = true) {  // ✅ Переименовали параметр!
    // Проверяем поддержку геолокации
    if (!navigator.geolocation) {
        if (shouldNotify) {
            showNotification('Геолокация не поддерживается вашим браузером', 'error');
        }
        return;
    }

    // Проверяем, есть ли сохранённая позиция
    const savedLocation = localStorage.getItem('userLocation');
    if (savedLocation) {
        const location = JSON.parse(savedLocation);
        state.userLocation = location;
        
        if (state.map) {
            state.map.setCenter([location.lat, location.lng], 13, { duration: 500 });
        }
        
        updateUserMarker();
        
        if (shouldNotify) {
            showNotification('📍 Местоположение определено (из кэша)', 'success');
        }
        
        setTimeout(() => loadStations(), 500);
        return;
    }

    // Запрашиваем геолокацию
    if (shouldNotify) {
        showNotification('🔍 Запрашиваем разрешение на геолокацию...', 'info');
    }

    navigator.geolocation.getCurrentPosition(
        // ✅ Успех
        (position) => {
            state.userLocation = {
                lat: position.coords.latitude,
                lng: position.coords.longitude,
                accuracy: position.coords.accuracy,
                timestamp: Date.now()
            };

            localStorage.setItem('userLocation', JSON.stringify(state.userLocation));

            if (state.map) {
                state.map.setCenter([state.userLocation.lat, state.userLocation.lng], 13, { duration: 500 });
            }

            updateUserMarker();

            if (shouldNotify) {
                showNotification('✅ Местоположение определено', 'success');
            }

            setTimeout(() => loadStations(), 500);
        },
        // ❌ Ошибка
        (error) => {
            console.error('Geolocation error:', error);
            
            let message = '';
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    message = '❌ Доступ к геолокации запрещён';
                    const requestBtn = document.getElementById('requestLocationBtn');
                    if (requestBtn) requestBtn.classList.remove('hidden');
                    break;
                case error.POSITION_UNAVAILABLE:
                    message = '⚠️ Информация о местоположении недоступна';
                    break;
                case error.TIMEOUT:
                    message = '⏱️ Превышено время ожидания';
                    break;
                default:
                    message = '❌ Неизвестная ошибка геолокации';
                    break;
            }
            
            if (shouldNotify) {
                showNotification(message, 'error');
            }
        },
        // ⚙️ Опции
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 300000
        }
    );
}

// ============================================
// ОБНОВИТЬ МАРКЕР ПОЛЬЗОВАТЕЛЯ
// ============================================
function updateUserMarker() {
    if (!state.map || !state.userLocation) return;

    // Удаляем старый маркер
    if (state.userPlacemark) {
        state.map.geoObjects.remove(state.userPlacemark);
    }

    // Создаём маркер пользователя (синяя точка с пульсацией)
    state.userPlacemark = new ymaps.Placemark(
        [state.userLocation.lat, state.userLocation.lng],
        {
            hintContent: '📍 Вы здесь',
            balloonContent: `
                <div style="padding: 10px; font-family: Arial, sans-serif;">
                    <strong>Ваше местоположение</strong><br>
                    <span style="font-size: 12px; color: #666;">
                        Широта: ${state.userLocation.lat.toFixed(6)}<br>
                        Долгота: ${state.userLocation.lng.toFixed(6)}
                    </span>
                </div>
            `
        },
        {
            preset: 'islands#blueCircleIcon',
            iconColor: '#3b82f6'
        }
    );

    state.map.geoObjects.add(state.userPlacemark);
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

// ============================================
// ГОЛОСОВАНИЕ ЗА ОТЧЁТ
// ============================================
async function voteForReport(station) {
    if (!station.latest_report_id) {
        showNotification('Нет активного отчёта для подтверждения', 'info');
        return;
    }
    
    try {
        const response = await fetch(`/api/reports/${station.latest_report_id}/vote`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ vote: 'up' })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            showNotification('Спасибо за подтверждение!', 'success');
            document.getElementById('stationModal').classList.add('hidden');
            loadStations();
        } else {
            showNotification(data.error || 'Ошибка подтверждения', 'error');
        }
    } catch (error) {
        console.error('Vote error:', error);
        showNotification('Ошибка при подтверждении', 'error');
    }
}