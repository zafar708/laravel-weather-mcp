<?php

namespace App\Support;

class WeatherConditions
{
    /**
     * Weather codes as described by the WMO 4677 standard.
     *
     * @var array<int, string>
     */
    protected static array $conditions = [
        0 => 'clear sky',
        1 => 'mainly clear',
        2 => 'partly cloudy',
        3 => 'overcast',
        45 => 'foggy',
        48 => 'depositing rime fog',
        51 => 'light drizzle',
        53 => 'moderate drizzle',
        55 => 'dense drizzle',
        61 => 'slight rain',
        63 => 'moderate rain',
        65 => 'heavy rain',
        71 => 'slight snowfall',
        73 => 'moderate snowfall',
        75 => 'heavy snowfall',
        80 => 'slight rain showers',
        81 => 'moderate rain showers',
        82 => 'violent rain showers',
        95 => 'thunderstorm',
        96 => 'thunderstorm with slight hail',
        99 => 'thunderstorm with heavy hail',
    ];

    /**
     * Describe a WMO weather code in plain language.
     */
    public static function describe(?int $code): string
    {
        return static::$conditions[$code] ?? 'unknown conditions';
    }
}
