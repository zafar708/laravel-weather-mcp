<?php

use App\Mcp\Servers\WeatherServer;
use App\Mcp\Tools\RainForecastTool;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

function fakeGeocoder(): array
{
    return [
        'geocoding-api.open-meteo.com/*' => Http::response([
            'results' => [[
                'name' => 'Narowal',
                'admin1' => 'Punjab',
                'country' => 'Pakistan',
                'latitude' => 32.1,
                'longitude' => 74.87,
            ]],
        ]),
    ];
}

it('reports the next hour rain is likely', function () {
    Carbon::setTestNow('2026-10-05T06:00:00+05:00');

    Http::fake(array_merge(fakeGeocoder(), [
        'api.open-meteo.com/*' => Http::response([
            'timezone' => 'Asia/Karachi',
            'hourly' => [
                'time' => ['2026-10-05T05:00', '2026-10-05T07:00', '2026-10-07T15:00'],
                'precipitation_probability' => [90, 10, 87],
                'precipitation' => [5.0, 0.0, 10.5],
                'weather_code' => [65, 0, 63],
            ],
            'daily' => [
                'time' => ['2026-10-05'],
                'precipitation_probability_max' => [87],
                'precipitation_sum' => [10.5],
                'weather_code' => [63],
            ],
        ]),
    ]));

    $response = WeatherServer::tool(RainForecastTool::class, [
        'location' => 'Narowal',
    ]);

    $response
        ->assertOk()
        // The 05:00 slot is already behind us and the 07:00 slot is only 10%,
        // so the first likely hour is the 87% slot on the 7th.
        ->assertSee('Wednesday, 7 October at 3 PM')
        ->assertSee('87% chance');
});

it('says so when no rain is expected', function () {
    Carbon::setTestNow('2026-10-05T06:00:00+05:00');

    Http::fake(array_merge(fakeGeocoder(), [
        'api.open-meteo.com/*' => Http::response([
            'timezone' => 'Asia/Karachi',
            'hourly' => [
                'time' => ['2026-10-05T07:00', '2026-10-05T08:00'],
                'precipitation_probability' => [5, 12],
                'precipitation' => [0.0, 0.0],
                'weather_code' => [0, 1],
            ],
            'daily' => [
                'time' => ['2026-10-05'],
                'precipitation_probability_max' => [12],
                'precipitation_sum' => [0.0],
                'weather_code' => [1],
            ],
        ]),
    ]));

    WeatherServer::tool(RainForecastTool::class, ['location' => 'Narowal'])
        ->assertOk()
        ->assertSee('No rain is expected in Narowal, Punjab, Pakistan');
});

it('passes the requested day count upstream', function () {
    Http::fake(array_merge(fakeGeocoder(), [
        'api.open-meteo.com/*' => Http::response([
            'timezone' => 'Asia/Karachi',
            'hourly' => ['time' => [], 'precipitation_probability' => [], 'precipitation' => [], 'weather_code' => []],
            'daily' => ['time' => [], 'precipitation_probability_max' => [], 'precipitation_sum' => [], 'weather_code' => []],
        ]),
    ]));

    WeatherServer::tool(RainForecastTool::class, [
        'location' => 'Narowal',
        'days' => 3,
    ])->assertOk();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'api.open-meteo.com/v1/forecast')
        && str_contains($request->url(), 'forecast_days=3'));
});

it('errors when the location cannot be found', function () {
    Http::fake([
        'geocoding-api.open-meteo.com/*' => Http::response(['results' => []]),
    ]);

    WeatherServer::tool(RainForecastTool::class, ['location' => 'Nowherecity'])
        ->assertHasErrors();
});

it('errors when the forecast service is unavailable', function () {
    Http::fake(array_merge(fakeGeocoder(), [
        'api.open-meteo.com/*' => Http::response([], 503),
    ]));

    WeatherServer::tool(RainForecastTool::class, ['location' => 'Narowal'])
        ->assertHasErrors();
});

it('rejects a day count outside the supported range', function () {
    WeatherServer::tool(RainForecastTool::class, [
        'location' => 'Narowal',
        'days' => 30,
    ])->assertHasErrors();
});

it('requires a location', function () {
    WeatherServer::tool(RainForecastTool::class, [])->assertHasErrors();
});
