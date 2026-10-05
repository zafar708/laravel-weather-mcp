<?php

namespace App\Mcp\Tools;

use App\Actions\ResolveLocation;
use App\Support\WeatherConditions;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Http;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Fetches the current weather for a specified location.')]
class CurrentWeatherTool extends Tool
{
    public function __construct(protected ResolveLocation $locations) {}

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $request->validate([
            'location' => 'required|string|max:100',
            'units' => 'sometimes|in:celsius,fahrenheit',
        ]);

        $units = $request->get('units', 'celsius');

        $place = $this->locations->handle($request->get('location'));

        if ($place === null) {
            return Response::error("Could not find a location named \"{$request->get('location')}\".");
        }

        $weather = $this->fetchWeather($place, $units);

        if ($weather === null) {
            return Response::error('The weather service is currently unavailable. Please try again.');
        }

        $symbol = $units === 'celsius' ? '°C' : '°F';

        return Response::json([
            'location' => $place['label'],
            'temperature' => $weather['temperature'],
            'units' => $units,
            'conditions' => $weather['conditions'],
            'humidity' => $weather['humidity'],
            'wind_speed' => $weather['wind_speed'],
            'observed_at' => $weather['observed_at'],
            'summary' => "The current weather in {$place['label']} is {$weather['temperature']}{$symbol} and {$weather['conditions']}.",
        ]);
    }

    /**
     * Fetch the current conditions for the given coordinates.
     *
     * @param  array{label: string, latitude: float, longitude: float}  $place
     * @return array{temperature: float, conditions: string, humidity: int, wind_speed: float, observed_at: string}|null
     */
    protected function fetchWeather(array $place, string $units): ?array
    {
        $response = Http::timeout(10)
            ->retry(2, 200)
            ->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $place['latitude'],
                'longitude' => $place['longitude'],
                'current' => 'temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m',
                'temperature_unit' => $units,
            ]);

        if ($response->failed()) {
            return null;
        }

        $current = $response->json('current');

        if (empty($current)) {
            return null;
        }

        $code = (int) ($current['weather_code'] ?? -1);

        return [
            'temperature' => round((float) $current['temperature_2m'], 1),
            'conditions' => WeatherConditions::describe($code),
            'humidity' => (int) ($current['relative_humidity_2m'] ?? 0),
            'wind_speed' => round((float) ($current['wind_speed_10m'] ?? 0), 1),
            'observed_at' => (string) ($current['time'] ?? ''),
        ];
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'location' => $schema->string()
                ->description('The city or place to get the weather for, e.g. "Lahore" or "New York".')
                ->required(),

            'units' => $schema->string()
                ->enum(['celsius', 'fahrenheit'])
                ->description('The temperature units to use.')
                ->default('celsius'),
        ];
    }

    /**
     * Get the tool's output schema.
     *
     * @return array<string, Type>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'location' => $schema->string()->description('The resolved location name.')->required(),
            'temperature' => $schema->number()->description('The current temperature.')->required(),
            'units' => $schema->string()->description('The temperature units used.')->required(),
            'conditions' => $schema->string()->description('A human readable description of the sky.')->required(),
            'humidity' => $schema->integer()->description('Relative humidity percentage.')->required(),
            'wind_speed' => $schema->number()->description('Wind speed at 10 metres.')->required(),
            'observed_at' => $schema->string()->description('The observation timestamp.')->required(),
            'summary' => $schema->string()->description('A one sentence summary of the weather.')->required(),
        ];
    }
}
