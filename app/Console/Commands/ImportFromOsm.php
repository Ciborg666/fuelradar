<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Station;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportFromOsm extends Command
{
    protected $signature = 'stations:import-osm {city} {--radius=15000}';
    protected $description = 'Импорт АЗС из OpenStreetMap (Overpass API)';

    // Несколько серверов для фолбэка
    protected $servers = [
        'https://overpass-api.de/api/interpreter',
        'https://overpass.kumi.systems/api/interpreter',
        'https://maps.mail.ru/osm/tools/overpass/api/interpreter',
    ];

    public function handle()
    {
        $city = $this->argument('city');
        $radius = (int)$this->option('radius');

        $coords = $this->getCityCoords($city);
        if (!$coords) {
            $this->error("❌ Не удалось определить координаты города");
            return 1;
        }

        $lat = $coords['lat'];
        $lon = $coords['lon'];
        $this->info("📍 Координаты: $lat, $lon");
        $this->info("📡 Радиус поиска: $radius м");

        // ✅ УПРОЩЁННЫЙ ЗАПРОС: только node (узлы)
        // Это намного быстрее чем искать node + way + relation
        $query = "[out:json][timeout:120];node[\"amenity\"=\"fuel\"](around:{$radius},{$lat},{$lon});out body;";

        $response = $this->tryServers($query);

        if (!$response || $response->failed()) {
            $this->error("❌ Все серверы Overpass API недоступны");
            $this->error("💡 Рекомендую использовать 2GIS API:");
            $this->error("   php artisan stations:import-2gis \"$city\"");
            return 1;
        }

        $data = $response->json();
        $elements = $data['elements'] ?? [];

        if (empty($elements)) {
            $this->warn("⚠️  Не найдено ни одной АЗС в указанном радиусе.");
            return 0;
        }

        $this->info("✅ Найдено объектов: " . count($elements));

        $bar = $this->output->createProgressBar(count($elements));
        $bar->start();

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($elements as $el) {
            $lat = $el['lat'] ?? null;
            $lon = $el['lon'] ?? null;
            
            if (!$lat || !$lon) {
                $skipped++;
                $bar->advance();
                continue;
            }

            $tags = $el['tags'] ?? [];
            $name = $tags['name'] ?? 'АЗС';
            $brandName = $tags['brand'] ?? $this->extractBrand($name);
            
            // Собираем адрес
            $address = $this->buildAddress($tags, $city);

            $brand = Brand::firstOrCreate(
                ['name' => $brandName],
                ['color_hex' => $this->getBrandColor($brandName)]
            );

            $station = Station::where('lat', $lat)->where('lng', $lon)->first();

            if ($station) {
                $station->update([
                    'name' => $name,
                    'brand_id' => $brand->id,
                    'address' => $address,
                    'city' => $city,
                ]);
                $updated++;
            } else {
                Station::create([
                    'brand_id' => $brand->id,
                    'name' => $name,
                    'address' => $address,
                    'lat' => $lat,
                    'lng' => $lon,
                    'city' => $city,
                ]);
                $created++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->table(
            ['Создано', 'Обновлено', 'Пропущено'],
            [[$created, $updated, $skipped]]
        );
        $this->info("✅ Импорт завершён!");
        return 0;
    }

    /**
     * Пробуем серверы по очереди
     */
    protected function tryServers(string $query)
    {
        foreach ($this->servers as $server) {
            $this->info("🔄 Пробую сервер: $server");
            
            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'FuelRadar-Importer/1.0',
                    'Accept' => 'application/json',
                ])
                ->withOptions([
                    'verify' => false,
                    'connect_timeout' => 15,
                ])
                ->timeout(120)
                ->asForm()
                ->post($server, ['data' => $query]);

                if ($response->ok()) {
                    $this->info("✅ Сервер отвечает!");
                    return $response;
                }

                $this->warn("⚠️  Сервер вернул статус: " . $response->status());
            } catch (\Exception $e) {
                $this->warn("⚠️  Ошибка: " . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Собрать адрес из тегов
     */
    protected function buildAddress(array $tags, string $city): string
    {
        $parts = [];
        
        if (!empty($tags['addr:street'])) {
            $parts[] = $tags['addr:street'];
        }
        if (!empty($tags['addr:housenumber'])) {
            $parts[] = $tags['addr:housenumber'];
        }
        
        if (empty($parts)) {
            return $city;
        }
        
        return implode(', ', $parts);
    }

    protected function getCityCoords(string $city): ?array
    {
        $url = "https://nominatim.openstreetmap.org/search?format=json&q=" 
             . urlencode($city) 
             . "&limit=1&countrycodes=ru";
        
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'FuelRadar-Importer/1.0',
            ])
            ->withOptions(['verify' => false])
            ->timeout(10)
            ->get($url);

            if (!$response->ok()) return null;
            $data = $response->json();
            if (empty($data)) return null;
            
            return [
                'lat' => (float)$data[0]['lat'], 
                'lon' => (float)$data[0]['lon']
            ];
        } catch (\Exception $e) {
            $this->error("Ошибка определения координат: " . $e->getMessage());
            return null;
        }
    }

    protected function extractBrand(string $name): string
    {
        $brands = ['Лукойл','Роснефть','Газпромнефть','Татнефть','Shell','BP',
                   'Магистраль','Трасса','Нефтьмагистраль','Bashneft','Сургутнефтегаз'];
        foreach ($brands as $brand) {
            if (stripos($name, $brand) !== false) return $brand;
        }
        return 'Независимая АЗС';
    }

    protected function getBrandColor(string $brandName): string
    {
        $colors = [
            'Лукойл' => '#0066CC',
            'Роснефть' => '#E31E24',
            'Газпромнефть' => '#0096D6',
            'Татнефть' => '#00A651',
            'Shell' => '#FFD500',
            'BP' => '#00843D',
            'Bashneft' => '#003366',
            'Сургутнефтегаз' => '#0072BC',
        ];
        foreach ($colors as $brand => $color) {
            if (stripos($brandName, $brand) !== false) return $color;
        }
        return '#666666';
    }
}