# Laravel Weather MCP Server

A small example of building **MCP (Model Context Protocol) servers in Laravel**.

It exposes live weather data and a searchable set of 50 seeded dummy users as MCP tools.
Ask *"when will it rain in Narowal?"* in an AI assistant, or search sample users in the
browser chat.

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

The app registers two MCP servers:

**Weather server** — two tools backed by the free [Open-Meteo](https://open-meteo.com)
API:

| Tool | Answers | Example input |
|---|---|---|
| `current-weather-tool` | What is the weather **right now**? | `{"location": "Lahore", "units": "celsius"}` |
| `rain-forecast-tool` | **When** will it next rain? Plus a 7-day outlook. | `{"location": "Narowal", "days": 7}` |

Both accept a plain place name. Geocoding (name → coordinates) happens for you.

**Users server** — searches up to 50 dummy user records in the `users` table:

| Tool | Answers | Example input |
|---|---|---|
| `user-information-tool` | Find sample users by name/email, search names by first letter, or list sample users. | `{"question": "Find Angus customer details"}` |

The users tool only returns records with the `dummy.user.*@example.test` email pattern.
It returns matching user IDs, names, and email addresses; it never returns passwords or
other account records.

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

**No API key is needed for the weather tools.** Open-Meteo is free and unauthenticated.

---

## Requirements

- PHP **8.3+** (developed on 8.4)
- Composer
- Node.js 18+ and npm — the browser tester is styled with Tailwind, built through Vite
- Outbound internet access, for the Open-Meteo API

Laravel 13 · `laravel/mcp` ^1.0 · SQLite (default, no database server to set up)

---

## Installation

```bash
git clone https://github.com/zafar708/laravel-weather-mcp.git
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

Seed sample users, then visit **http://localhost:8000/mcp-tester** for the weather tools
or **http://localhost:8000/users-chat** to chat with the users MCP tool:

```bash
php artisan db:seed
```

> Migrations are Laravel's default tables (users, cache, jobs). The weather tools do not
> use the database at all — they call Open-Meteo directly. The users tool needs the seeded
> dummy records; `php artisan db:seed` can be run again safely.

---

## Ways to use it

### 1. Browser tools (start here)

#### Weather tester

```
http://localhost:8000/mcp-tester
```

A small page with three buttons that send **real JSON-RPC requests** to the MCP endpoint:

- **List tools** — what the server advertises (this is `tools/list`)
- **Current weather** — live conditions
- **When will it rain?** — next rain plus a 7-day chart

The page is styled with Tailwind and loaded through Vite, so the assets must be built.
`composer setup` does this for you. While changing the page, run `npm run dev` for hot
reloading, or `npm run build` once if you only want to view it.

Each button shows a formatted result on top and the **raw JSON-RPC response** below, so you
can watch the protocol itself. This is the fastest way to understand what MCP is actually
exchanging.

#### Users chat

```
http://localhost:8000/users-chat
```

Ask questions such as **"Find the Angus customer details"** or
**"Is there any customer name starting with A?"**. Questions and replies stay visible in
the chat, and the page calls `user-information-tool` through `POST /mcp/users`. Run
`php artisan db:seed` first if the sample users have not been seeded yet.

### 2. From an AI assistant

The repo ships a `.mcp.json`, so **Claude Code picks the server up automatically** when you
open the project. Reload your editor, then ask:

> "When will it rain in Narowal?"

The `.mcp.json` currently registers the weather server. To let Claude Code use the users
server too, add this entry to its `mcpServers` object:

```json
"users": {
    "command": "php",
    "args": ["artisan", "mcp:start", "users"]
}
```

Reload your editor and ask questions such as **"Find the Angus customer details"**. Make
sure the sample users have been seeded with `php artisan db:seed`.

For other clients, register a local server with:

```
command: php
args:    ["artisan", "mcp:start", "weather"]
```

For the users server, use `["artisan", "mcp:start", "users"]`.

> Do not run `php artisan mcp:start weather` by hand in a terminal — it waits on stdin and
> will appear to hang. It is meant to be launched by an MCP client.

The official inspector is the best debugging UI:

```bash
php artisan mcp:inspector weather       # local (stdio) server
php artisan mcp:inspector mcp/weather   # HTTP server
php artisan mcp:inspector users         # local users server
php artisan mcp:inspector mcp/users     # HTTP users server
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

The dummy-user server is exposed at `POST /mcp/users`. For example, search for users by
name:

```bash
curl -X POST http://localhost:8000/mcp/users \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/call",
       "params":{"name":"user-information-tool","arguments":{"question":"Find Angus customer details"}}}'
```

To list the available user tools, send `{"jsonrpc":"2.0","id":1,"method":"tools/list"}` to
the same endpoint.

---

## How the code fits together

```
routes/ai.php                          Registers both servers (local + web)
├── app/Mcp/Servers/WeatherServer.php  Declares the weather tools
│   ├── app/Mcp/Tools/CurrentWeatherTool.php
│   └── app/Mcp/Tools/RainForecastTool.php
│       ├── app/Actions/ResolveLocation.php     place name → coordinates
│       └── app/Support/WeatherConditions.php   WMO codes → plain English
└── app/Mcp/Servers/UserServer.php     Declares the dummy-user tool
    └── app/Mcp/Tools/UserInformationTool.php
            └── database/seeders/DummyUsersSeeder.php
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

`routes/ai.php` registers each server for local stdio clients and over HTTP:

```php
Mcp::local('weather', WeatherServer::class);                 // stdio, for local AI clients
Mcp::web('/mcp/weather', WeatherServer::class)               // HTTP, for remote clients
    ->middleware(['throttle:60,1']);

Mcp::local('users', UserServer::class);
Mcp::web('/mcp/users', UserServer::class)
    ->middleware(['throttle:60,1']);
```

---

## Tests

```bash
composer test
```

The weather tests fake outbound HTTP calls, so they are fast, offline, and deterministic.
Feature tests also cover the user chat, dummy-user searches, and MCP HTTP endpoint.
`laravel/mcp` lets you invoke a tool directly:

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

**The web endpoints have no authentication.** `/mcp/weather` and `/mcp/users` are open,
rate-limited only by `throttle:60,1`. The users tool is restricted to the seeded dummy
email pattern, but do not change it to return real or private account data without adding
authentication and authorization first:

```php
Mcp::web('/mcp/weather', WeatherServer::class)
    ->middleware(['auth:sanctum']);
```

**`/mcp-tester` and `/users-chat` are development pages.** They are unauthenticated and
expose their respective MCP endpoints through the browser. Remove them or protect them
before going to production.

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
registered the same way, in either MCP server:

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
