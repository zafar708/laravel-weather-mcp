<?php

namespace App\Mcp\Tools;

use App\Actions\ResolveLocation;
use App\Support\WeatherConditions;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Predicts when rain is next expected at a location, with a day-by-day rain outlook for the week ahead.')]
class RainForecastTool extends Tool
{
    /**
     * The probability at or above which rain is considered likely.
     */
    protected int $threshold = 50;

    public function __construct(protected ResolveLocation $locations) {}

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $request->validate([
            'location' => 'required|string|max:100',
            'days' => 'sometimes|integer|min:1|max:16',
        ]);

        $days = (int) $request->get('days', 7);

        $place = $this->locations->handle($request->get('location'));

        if ($place === null) {
            return Response::error('Could not find a location named "'.$request->get('location').'".');
        }

        $forecast = $this->fetchForecast($place, $days);

        if ($forecast === null) {
            return Response::error('The weather service is currently unavailable. Please try again.');
        }

        return Response::json([
            'location' => $place['label'],
            'timezone' => $forecast['timezone'],
            'next_rain' => $forecast['next_rain'],
            'daily' => $forecast['daily'],
            'summary' => $this->summarise($place['label'], $forecast['next_rain']),
        ]);
    }

    /**
     * Retrieve the hourly and daily precipitation outlook.
     *
     * @param  array{label: string, latitude: float, longitude: float}  $place
     * @return array{timezone: string, next_rain: array<string, mixed>|null, daily: array<int, array<string, mixed>>}|null
     */
    protected function fetchForecast(array $place, int $days): ?array
    {
        $response = Http::timeout(10)
            ->retry(2, 200)
            ->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $place['latitude'],
                'longitude' => $place['longitude'],
                'hourly' => 'precipitation_probability,precipitation,weather_code',
                'daily' => 'precipitation_probability_max,precipitation_sum,weather_code',
                'forecast_days' => $days,
                'timezone' => 'auto',
            ]);

        if ($response->failed()) {
            return null;
        }

        $hourly = $response->json('hourly');
        $daily = $response->json('daily');

        if (empty($hourly) || empty($daily)) {
            return null;
        }

        $timezone = (string) $response->json('timezone', 'UTC');

        return [
            'timezone' => $timezone,
            'next_rain' => $this->findNextRain($hourly, $timezone),
            'daily' => $this->mapDays($daily),
        ];
    }

    /**
     * Find the first upcoming hour where rain is likely.
     *
     * @param  array<string, array<int, mixed>>  $hourly
     * @return array<string, mixed>|null
     */
    protected function findNextRain(array $hourly, string $timezone): ?array
    {
        $now = Carbon::now($timezone);

        foreach ($hourly['time'] as $index => $time) {
            $moment = Carbon::parse($time, $timezone);

            // The API returns the whole of today, so skip hours already behind us.
            if ($moment->lessThan($now)) {
                continue;
            }

            $probability = (int) ($hourly['precipitation_probability'][$index] ?? 0);

            if ($probability < $this->threshold) {
                continue;
            }

            return [
                'time' => $moment->toIso8601String(),
                'readable' => $moment->isoFormat('dddd, D MMMM [at] h A'),
                'hours_from_now' => (int) $now->diffInHours($moment),
                'probability' => $probability,
                'amount_mm' => round((float) ($hourly['precipitation'][$index] ?? 0), 2),
                'conditions' => WeatherConditions::describe((int) ($hourly['weather_code'][$index] ?? -1)),
            ];
        }

        return null;
    }

    /**
     * Build a day-by-day rain outlook.
     *
     * @param  array<string, array<int, mixed>>  $daily
     * @return array<int, array<string, mixed>>
     */
    protected function mapDays(array $daily): array
    {
        return collect($daily['time'])
            ->map(fn (string $date, int $index) => [
                'date' => $date,
                'readable' => Carbon::parse($date)->isoFormat('ddd, D MMM'),
                'chance' => (int) ($daily['precipitation_probability_max'][$index] ?? 0),
                'amount_mm' => round((float) ($daily['precipitation_sum'][$index] ?? 0), 2),
                'conditions' => WeatherConditions::describe((int) ($daily['weather_code'][$index] ?? -1)),
            ])
            ->all();
    }

    /**
     * Describe the outlook in a single sentence.
     *
     * @param  array<string, mixed>|null  $next
     */
    protected function summarise(string $label, ?array $next): string
    {
        if ($next === null) {
            return 'No rain is expected in '.$label.' for the period covered by this forecast.';
        }

        return 'Rain is expected in '.$label.' on '.$next['readable']
            .' ('.$next['probability'].'% chance, about '.$next['amount_mm'].'mm).';
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
                ->description('The city or place to forecast rain for, e.g. "Narowal" or "Lahore".')
                ->required(),

            'days' => $schema->integer()
                ->description('How many days ahead to look, between 1 and 16.')
                ->default(7),
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
            'timezone' => $schema->string()->description('The timezone the forecast times are given in.')->required(),
            'next_rain' => $schema->object()->description('The next hour rain is likely, or null if none is expected.'),
            'daily' => $schema->array()->description('A day-by-day rain outlook.')->required(),
            'summary' => $schema->string()->description('A one sentence summary of the outlook.')->required(),
        ];
    }
}
