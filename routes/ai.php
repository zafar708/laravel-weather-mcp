<?php

use App\Mcp\Servers\WeatherServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::local('weather', WeatherServer::class);

Mcp::web('/mcp/weather', WeatherServer::class)
    ->middleware(['throttle:60,1']);
