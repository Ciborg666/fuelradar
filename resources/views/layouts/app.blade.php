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
    
  <!-- Яндекс.Карты API -->
    <script src="https://api-maps.yandex.ru/2.1/?apikey=1f51f1c5-a3cf-474d-b85a-9a80dd9c1c06&lang=ru_RU&suggest_apikey=1f51f1c5-a3cf-474d-b85a-9a80dd9c1c06" type="text/javascript"></script>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Наши стили -->
    <link rel="stylesheet" href="{{ asset('css/fuelradar.css') }}">
    
    <!-- Тема -->
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'dark';
            if (theme === 'light') document.documentElement.classList.add('light');
        })();
    </script>
</head>
<body class="font-sans antialiased bg-slate-900 text-white overflow-hidden">
    @yield('content')
</body>
</html>