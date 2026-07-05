<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\FuelType;
use App\Models\Station;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Бренды
        $brands = [
            ['name' => 'Лукойл', 'color_hex' => '#0066CC'],
            ['name' => 'Роснефть', 'color_hex' => '#E31E24'],
            ['name' => 'Газпромнефть', 'color_hex' => '#0096D6'],
            ['name' => 'Татнефть', 'color_hex' => '#00A651'],
            ['name' => 'Shell', 'color_hex' => '#FFD500'],
        ];
        
        foreach ($brands as $brand) {
            Brand::create($brand);
        }
        
        // Типы топлива
        $fuels = [
            ['name' => 'АИ-92', 'short_name' => '92'],
            ['name' => 'АИ-95', 'short_name' => '95'],
            ['name' => 'АИ-98', 'short_name' => '98'],
            ['name' => 'АИ-100', 'short_name' => '100'],
            ['name' => 'ДТ', 'short_name' => 'DT'],
        ];
        
        foreach ($fuels as $fuel) {
            FuelType::create($fuel);
        }
        
        // Тестовые станции (Москва)
        $stations = [
            ['brand_id' => 1, 'name' => 'Лукойл №1', 'address' => 'ул. Тверская, 1', 'lat' => 55.7558, 'lng' => 37.6173, 'city' => 'Москва'],
            ['brand_id' => 2, 'name' => 'Роснефть №5', 'address' => 'проспект Мира, 10', 'lat' => 55.7698, 'lng' => 37.6343, 'city' => 'Москва'],
            ['brand_id' => 3, 'name' => 'Газпромнефть №12', 'address' => 'ул. Арбат, 5', 'lat' => 55.7500, 'lng' => 37.6000, 'city' => 'Москва'],
            
            // 🏭 Заправки в Новокузнецке
            ['brand_id' => 1, 'name' => 'Лукойл', 'address' => 'проспект Бардина, 38', 'lat' => 53.7596, 'lng' => 87.1216, 'city' => 'Новокузнецк'],
            ['brand_id' => 2, 'name' => 'Роснефть', 'address' => 'ул. Кирова, 52', 'lat' => 53.7520, 'lng' => 87.1150, 'city' => 'Новокузнецк'],
            ['brand_id' => 3, 'name' => 'Газпромнефть', 'address' => 'проспект Курако, 15', 'lat' => 53.7650, 'lng' => 87.1280, 'city' => 'Новокузнецк'],

          // 🏭 Заправки в Рубцовске (Алтайский край)
            ['brand_id' => 1, 'name' => 'Лукойл', 'address' => 'улица Ворошилова, 64', 'lat' => 51.4972, 'lng' => 81.2038, 'city' => 'Рубцовск'],
            ['brand_id' => 2, 'name' => 'Роснефть', 'address' => 'проспект Ленина, 194', 'lat' => 51.5015, 'lng' => 81.2150, 'city' => 'Рубцовск'],
            ['brand_id' => 3, 'name' => 'Газпромнефть', 'address' => 'улица 30 лет Победы, 28', 'lat' => 51.4920, 'lng' => 81.1950, 'city' => 'Рубцовск'],
        ];
        
        foreach ($stations as $station) {
            Station::create($station);
        }
    }
}