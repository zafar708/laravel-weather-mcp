<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\CurrentWeatherTool;
use App\Mcp\Tools\RainForecastTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Tool;

#[Name('Weather Server')]
#[Version('1.0.0')]
#[Instructions('Provides current weather conditions for any location worldwide. Use the current-weather tool with a city name to retrieve live temperature, sky conditions, humidity, and wind speed.')]
class WeatherServer extends Server
{
    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        CurrentWeatherTool::class,
        RainForecastTool::class,
    ];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<Server\Resource>>
     */
    protected array $resources = [
        //
    ];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<Prompt>>
     */
    protected array $prompts = [
        //
    ];
}
