# Laravel Weather MCP Server

A small, complete example of building an **MCP (Model Context Protocol) server in Laravel**.

It exposes live weather data as two tools that any AI assistant — Claude Code, Claude
Desktop, Cursor — can call directly. Ask *"when will it rain in Narowal?"* in your
assistant and it will call this Laravel app and answer from real data.

The project is deliberately small. It is meant to be **read**, not just run: if you want to
understand how `laravel/mcp` fits together, this is roughly the smallest useful thing you
can build with it.

---

## What is MCP, in one paragraph

MCP is an open standard that lets AI assistants call external tools. An MCP server
advertises what it can do (`tools/list`), and the assistant calls what it needs
(`tools/call`). Think of it as a USB-C port for AI: write the server once, and any
MCP-compatible client can use it. This repo is the server side of that conversation.

---

## What this app does

Two tools, both backed by the free [Open-Meteo](https://open-meteo.com) API:

| Tool | Answers | Example input |
|---|---|---|
| `current-weather-tool` | What is the weather **right now**? | `{"location": "Lahore", "units": "celsius"}` |
| `rain-forecast-tool` | **When** will it next rain? Plus a 7-day outlook. | `{"location": "Narowal", "days": 7}` |

Both accept a plain place name. Geocoding (name → coordinates) happens for you.

Sample response from `rain-forecast-tool`:

```json
{
  "location": "Narowal, Punjab, Pakistan",
  "timezone": "Asia/Karachi",
  "next_rain": {
    "readable": "Wednesday, 7 October at 1 AM",
    "hours_from_now": 35,
    "probability": 60,
    "amount_mm": 2.2,
    "conditions": "slight rain showers"
  },
  "daily": [ { "readable": "Mon, 5 Oct", "chance": 0, "amount_mm": 0 }, "..." ],
  "summary": "Rain is expected in Narowal, Punjab, Pakistan on Wednesday, 7 October at 1 AM (60% chance, about 2.2mm)."
}
```

**No API key is needed.** Open-Meteo is free and unauthenticated, so the project works
the moment you install it.

---

## Requirements

- PHP **8.3+** (developed on 8.4)
- Composer
- Node.js 18+ and npm — only for the frontend build
- Outbound internet access, for the Open-Meteo API

Laravel 13 · `laravel/mcp` ^1.0 · SQLite (default, no database server to set up)

---

## Installation

```bash
git clone <your-repo-url> laravel-weather-mcp
cd laravel-weather-mcp
composer setup
```

`composer setup` does everything: installs dependencies, creates `.env`, generates the app
key, runs migrations, and builds the frontend.

<details>
<summary>Prefer to run the steps yourself?</summary>

```bash
composer install
cp .env.example .env        # Windows: copy .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

</details>

Then start the app:

```bash
php artisan serve
```

Visit **http://localhost:8000/mcp-tester** to try it in your browser.

> Migrations are Laravel's default tables (users, cache, jobs). The weather tools do not
> use the database at all — they call Open-Meteo directly.

---

## Three ways to use it

### 1. Browser tester (start here)

```
http://localhost:8000/mcp-tester
```

A small page with three buttons that send **real JSON-RPC requests** to the MCP endpoint:

- **List tools** — what the server advertises (this is `tools/list`)
- **Current weather** — live conditions
- **When will it rain?** — next rain plus a 7-day chart

Each button shows a formatted result on top and the **raw JSON-RPC response** below, so you
can watch the protocol itself. This is the fastest way to understand what MCP is actually
exchanging.

### 2. From an AI assistant

The repo ships a `.mcp.json`, so **Claude Code picks the server up automatically** when you
open the project. Reload your editor, then ask:

> "When will it rain in Narowal?"

For other clients, register the local server with:

```
command: php
args:    ["artisan", "mcp:start", "weather"]
```

> Do not run `php artisan mcp:start weather` by hand in a terminal — it waits on stdin and
> will appear to hang. It is meant to be launched by an MCP client.

The official inspector is the best debugging UI:

```bash
php artisan mcp:inspector weather       # local (stdio) server
php artisan mcp:inspector mcp/weather   # HTTP server
```

### 3. Over HTTP

The same server is exposed at `POST /mcp/weather`:

```bash
curl -X POST http://localhost:8000/mcp/weather \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/call",
       "params":{"name":"rain-forecast-tool","arguments":{"location":"Narowal"}}}'
```

List what is available:

```bash
curl -X POST http://localhost:8000/mcp/weather \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/list"}'
```

> Opening `/mcp/weather` in the address bar will not work. Tool calls are **POST**
> JSON-RPC; a browser address bar sends GET.

---

## How the code fits together

```
routes/ai.php                          Registers the server (local + web)
└── app/Mcp/Servers/WeatherServer.php   Declares which tools exist
    ├── app/Mcp/Tools/CurrentWeatherTool.php
    └── app/Mcp/Tools/RainForecastTool.php
            ├── app/Actions/ResolveLocation.php     place name → coordinates
            └── app/Support/WeatherConditions.php   WMO codes → plain English
```

A tool is just a class with three parts:

```php
#[Description('Predicts when rain is next expected at a location.')]  // what it is for
class RainForecastTool extends Tool
{
    public function handle(Request $request): Response { /* what it does */ }

    public function schema(JsonSchema $schema): array  { /* what it accepts */ }
}
```

The `#[Description]` attribute and `schema()` method are **not decoration** — they are the
only documentation the AI ever sees. Run the **List tools** button and you will see them
come back verbatim in the JSON. A vague description means the model misuses the tool or
skips it entirely.

`routes/ai.php` registers the same server two ways:

```php
Mcp::local('weather', WeatherServer::class);                 // stdio, for local AI clients
Mcp::web('/mcp/weather', WeatherServer::class)               // HTTP, for remote clients
    ->middleware(['throttle:60,1']);
```

---

## Tests

```bash
composer test
```

14 passing tests. The weather tests fake all HTTP calls, so the suite is fast, offline, and
deterministic. `laravel/mcp` lets you invoke a tool directly:

```php
WeatherServer::tool(RainForecastTool::class, ['location' => 'Narowal'])
    ->assertOk()
    ->assertSee('87% chance');
```

Code style:

```bash
./vendor/bin/pint
```

---

## Things worth knowing before you build on this

**The web endpoint has no authentication.** `/mcp/weather` is open, rate-limited only by
`throttle:60,1`. That is fine for public weather data on your machine. Before deploying
anything that touches private data, add real auth:

```php
Mcp::web('/mcp/weather', WeatherServer::class)
    ->middleware(['auth:sanctum']);
```

**`/mcp-tester` is a development page.** It is unauthenticated and exposes your MCP
endpoint through the browser. Remove it or protect it before going to production.

**Tool names are derived from class names.** `RainForecastTool` becomes
`rain-forecast-tool`. If you want a different name, set it explicitly:

```php
protected string $name = 'rain-forecast';
```

**Rain is reported at 50% probability or above.** Anything less is treated as "no rain".
Adjust the threshold in `RainForecastTool`:

```php
protected int $threshold = 50;  // 30 = more alerts, 70 = only near-certain rain
```

---

## Where to go next

Tools are one of three MCP primitives. This project only uses tools; **resources**
(read-only data the AI can pull in) and **prompts** (reusable prompt templates) are
registered the same way, in `WeatherServer`:

```bash
php artisan make:mcp-resource WeatherGuidelines
php artisan make:mcp-prompt DescribeWeather
```

Good next exercises: add a tool that returns a multi-day temperature forecast, expose a
resource explaining how to read WMO weather codes, or swap Open-Meteo for a provider that
needs an API key and move the credential into `config/services.php`.

- [Laravel MCP documentation](https://laravel.com/docs/mcp)
- [Model Context Protocol specification](https://modelcontextprotocol.io)
- [Open-Meteo API docs](https://open-meteo.com/en/docs)

---

## License

MIT.
