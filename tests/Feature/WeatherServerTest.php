<?php

use App\Mcp\Servers\WeatherServer;
use App\Mcp\Tools\CurrentWeatherTool;
use Illuminate\Support\Facades\Http;

it('reports the current weather for a location', function () {
    Http::fake([
        'geocoding-api.open-meteo.com/*' => Http::response([
            'results' => [[
                'name' => 'Lahore',
                'admin1' => 'Punjab',
                'country' => 'Pakistan',
                'latitude' => 31.5497,
                'longitude' => 74.3436,
            ]],
        ]),
        'api.open-meteo.com/*' => Http::response([
            'current' => [
                'time' => '2026-10-05T09:00',
                'temperature_2m' => 31.4,
                'relative_humidity_2m' => 48,
                'weather_code' => 1,
                'wind_speed_10m' => 12.3,
            ],
        ]),
    ]);

    $response = WeatherServer::tool(CurrentWeatherTool::class, [
        'location' => 'Lahore',
    ]);

    $response
        ->assertOk()
        ->assertSee('The current weather in Lahore, Punjab, Pakistan is 31.4°C and mainly clear.');
});

it('requests fahrenheit from the upstream service when asked', function () {
    Http::fake([
        'geocoding-api.open-meteo.com/*' => Http::response([
            'results' => [[
                'name' => 'New York',
                'country' => 'United States',
                'latitude' => 40.71,
                'longitude' => -74.01,
            ]],
        ]),
        'api.open-meteo.com/*' => Http::response([
            'current' => [
                'time' => '2026-10-05T09:00',
                'temperature_2m' => 72.0,
                'relative_humidity_2m' => 55,
                'weather_code' => 0,
                'wind_speed_10m' => 8.0,
            ],
        ]),
    ]);

    $response = WeatherServer::tool(CurrentWeatherTool::class, [
        'location' => 'New York',
        'units' => 'fahrenheit',
    ]);

    $response->assertOk()->assertSee('72°F and clear sky');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'api.open-meteo.com/v1/forecast')
        && str_contains($request->url(), 'temperature_unit=fahrenheit'));
});

it('errors when the location cannot be found', function () {
    Http::fake([
        'geocoding-api.open-meteo.com/*' => Http::response(['results' => []]),
    ]);

    WeatherServer::tool(CurrentWeatherTool::class, [
        'location' => 'Nowherecity',
    ])->assertHasErrors();
});

it('errors when the weather service is unavailable', function () {
    Http::fake([
        'geocoding-api.open-meteo.com/*' => Http::response([
            'results' => [[
                'name' => 'Lahore',
                'country' => 'Pakistan',
                'latitude' => 31.5497,
                'longitude' => 74.3436,
            ]],
        ]),
        'api.open-meteo.com/*' => Http::response([], 503),
    ]);

    WeatherServer::tool(CurrentWeatherTool::class, [
        'location' => 'Lahore',
    ])->assertHasErrors();
});

it('requires a location', function () {
    WeatherServer::tool(CurrentWeatherTool::class, [])->assertHasErrors();
});
