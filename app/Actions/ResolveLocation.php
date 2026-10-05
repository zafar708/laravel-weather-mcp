<?php

namespace App\Actions;

use Illuminate\Support\Facades\Http;

class ResolveLocation
{
    /**
     * Translate a free-form place name into coordinates.
     *
     * @return array{label: string, latitude: float, longitude: float}|null
     */
    public function handle(string $location): ?array
    {
        $response = Http::timeout(10)
            ->retry(2, 200)
            ->get('https://geocoding-api.open-meteo.com/v1/search', [
                'name' => $location,
                'count' => 1,
                'language' => 'en',
                'format' => 'json',
            ]);

        if ($response->failed()) {
            return null;
        }

        $result = $response->json('results.0');

        if (empty($result)) {
            return null;
        }

        $label = collect([$result['name'] ?? null, $result['admin1'] ?? null, $result['country'] ?? null])
            ->filter()
            ->unique()
            ->implode(', ');

        return [
            'label' => $label,
            'latitude' => (float) $result['latitude'],
            'longitude' => (float) $result['longitude'],
        ];
    }
}
