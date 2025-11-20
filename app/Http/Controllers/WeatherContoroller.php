<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class WeatherController extends Controller
{
    private $apiKey;
    private $city;
    private $cacheDuration;
    
    public function __construct()
    {
        $this->apiKey = Config::get('services.openweather.key');
        $this->city = Config::get('services.openweather.city');
        $this->cacheDuration = Config::get('services.openweather.cache_duration');
    }

    public function getWeather()
    {
        $cacheKey = 'weather_' . strtolower(str_replace(',', '_', $this->city));
        
        return Cache::remember($cacheKey, $this->cacheDuration, function () {
            try {
                // 現在の天気を取得
                $currentWeather = Http::get("https://api.openweathermap.org/data/2.5/weather", [
                    'q' => $this->city,
                    'appid' => $this->apiKey,
                    'units' => 'metric',
                    'lang' => 'ja'
                ]);

                if ($currentWeather->failed()) {
                    throw new \Exception('Current weather API request failed: ' . $currentWeather->status());
                }

                $currentData = $currentWeather->json();

                // 天気予報を取得
                $forecast = Http::get("https://api.openweathermap.org/data/2.5/forecast", [
                    'q' => $this->city,
                    'appid' => $this->apiKey,
                    'units' => 'metric',
                    'lang' => 'ja'
                ]);

                if ($forecast->failed()) {
                    throw new \Exception('Forecast API request failed: ' . $forecast->status());
                }

                $forecastData = $forecast->json();

                return [
                    'current' => [
                        'temp' => round($currentData['main']['temp']),
                        'feels_like' => round($currentData['main']['feels_like']),
                        'humidity' => $currentData['main']['humidity'],
                        'description' => $currentData['weather'][0]['description'],
                        'icon' => $currentData['weather'][0]['icon']
                    ],
                    'forecast' => collect($forecastData['list'])
                        ->take(3)
                        ->map(function ($item) {
                            return [
                                'time' => date('H:i', $item['dt']),
                                'temp' => round($item['main']['temp']),
                                'description' => $item['weather'][0]['description'],
                                'icon' => $item['weather'][0]['icon']
                            ];
                        })
                ];

            } catch (\Exception $e) {
                \Log::error('Weather API Error: ' . $e->getMessage());
                return [
                    'error' => true,
                    'message' => '天気データの取得に失敗しました'
                ];
            }
        });
    }
}