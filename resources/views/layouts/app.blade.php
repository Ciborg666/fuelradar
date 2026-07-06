<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', config('app.name', 'FuelRadar'))</title>
    
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⛽</text></svg>">
    
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />
    
    {{-- ✅ Яндекс.Карты API — ТОЛЬКО ОДИН РАЗ, через переменную окружения --}}
    <script src="https://api-maps.yandex.ru/2.1/?apikey={{ config('services.yandex_maps.key') }}&lang=ru_RU" defer></script>
    
    {{-- Tailwind CSS --}}
    <script src="https://cdn.tailwindcss.com"></script>
    
    {{-- ✅ Наши стили через secure_asset для HTTPS --}}
    <link rel="stylesheet" href="{{ safe_asset('css/fuelradar.css') }}">
    
    {{-- Тема --}}
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'dark';
            if (theme === 'light') document.documentElement.classList.add('light');
        })();
    </script>
    
    @stack('styles')
</head>
<body class="font-sans antialiased bg-slate-900 text-white overflow-hidden">
    @yield('content')
    
    @stack('scripts')
</body>
</html>