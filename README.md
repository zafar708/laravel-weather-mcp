# Laravel MCP + AI SDK Demo

A small, readable example of the two halves of AI in Laravel:

- **`laravel/mcp`** exposes your app's features as tools that AI assistants can call.
- **`laravel/ai`** (the Laravel AI SDK) puts an AI model *inside* your app, so it can decide
  which of those tools to call.

The app has three tools: live weather, a rain forecast, and a search over 50 seeded dummy
users. You can call them by hand, let an outside assistant such as Claude Code call them over
MCP, or ask the built-in Gemini-powered chat a question like *"Karachi mein kal baarish
hogi?"* and watch it pick the tool itself.

The project is deliberately small. It is meant to be **read**, not just run.

---

## MCP vs the AI SDK, in one paragraph each

**MCP** is an open standard that lets AI assistants call external tools. An MCP server
advertises what it can do (`tools/list`), and the assistant calls what it needs
(`tools/call`). MCP itself has no intelligence: when *you* call a tool by hand, nothing
"understands" anything, the tool just runs its PHP code.

**The AI SDK** sends a user's message, plus the list of available tools, to an AI provider
(Gemini, OpenAI, Anthropic, and others). The model decides which tool to call and with what
arguments. Your app runs the tool and sends the result back, and the model writes the final
answer. The tool code is identical in both cases. What changes is **who decides** to call it.

| | Calling the MCP tool directly | AI SDK agent |
|---|---|---|
| Who picks the tool and arguments | You (a button, a form, or code) | The AI model |
| Understands free text such as "first 5 users" | No | Yes |
| Speed | Well under a second | Several seconds (two or more model calls) |
| Cost | Free | API usage per question |
| Same answer every time | Yes | Not guaranteed |

---

## What this app does

**Weather tools.** Backed by the free [Open-Meteo](https://open-meteo.com) API. No API key
is needed.

| Tool | Answers | Example input |
|---|---|---|
| `current-weather-tool` | What is the weather **right now**? | `{"location": "Lahore", "units": "celsius"}` |
| `rain-forecast-tool` | **When** will it next rain? Plus a day-by-day outlook. | `{"location": "Narowal", "days": 7}` |

Both accept a plain place name. Geocoding (name to coordinates) happens for you.

**Users tool.** Searches the 50 dummy records in the `users` table. Every filter is
optional; with no arguments it lists all dummy users in ID order.

| Argument | Meaning | Example |
|---|---|---|
| `search` | Part of a name or email | `"Amina"` |
| `starts_with` | Names beginning with these letters | `"A"` |
| `limit` | At most this many users (1 to 50) | `5` returns the first five |

The users tool only returns records matching `dummy.user.*@example.test`, and only their
IDs, names, and email addresses.

**Pages.**

| URL | What it shows | AI involved? |
|---|---|---|
| `/mcp-tester` | Buttons that send raw JSON-RPC calls to the weather MCP server | No |
| `/users-chat` | A search form that calls the users MCP tool directly | No |
| `/ai-chat` | A chat where Gemini chooses and calls all three tools | **Yes** |

---

## Requirements

- PHP **8.3+** (developed on 8.4)
- Composer
- Node.js 18+ and npm (pages are styled with Tailwind, built through Vite)
- Outbound internet access, for Open-Meteo and the AI provider
- A **Gemini API key**, only for `/ai-chat`. Everything else works without one.

Laravel 13 · `laravel/mcp` ^1.0 · `laravel/ai` ^1.1 · SQLite by default

---

## Installation

```bash
git clone https://github.com/zafar708/laravel-weather-mcp.git
cd laravel-weather-mcp
composer setup
php artisan db:seed
```

`composer setup` installs dependencies, creates `.env`, generates the app key, runs
migrations, and builds the frontend. `db:seed` creates the 50 dummy users and is safe to
run again.

<details>
<summary>Prefer to run the steps yourself?</summary>

```bash
composer install
cp .env.example .env        # Windows: copy .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm install
npm run build
```

</details>

### Add a Gemini API key (for `/ai-chat`)

1. Go to [Google AI Studio](https://aistudio.google.com), sign in, and choose **Get API key**,
   then **Create API key**.
2. Put it in `.env`:

   ```ini
   GEMINI_API_KEY=your-key-here
   ```

3. If you have cached config, run `php artisan config:clear`.

Never commit `.env`. `.env.example` keeps the variable empty on purpose.

Then start the app:

```bash
php artisan serve
```

and open **http://localhost:8000/ai-chat**, **/users-chat**, or **/mcp-tester**.

---

## Ways to use it

### 1. The AI chat (`/ai-chat`)

Ask in English or Roman Urdu, for example:

- *"Lahore mein abhi mausam kaisa hai?"*
- *"Karachi mein kal baarish hogi?"*
- *"Give the first 5 users details"*

Under each reply the page shows which tool the model called and with which arguments, such
as `rain-forecast-tool({"location":"Karachi","days":2})` or
`user-information-tool({"limit":5})`. Nobody wrote code to turn "kal" into `days: 2` or
"first 5" into `limit: 5`; the model did that from the tool's description and schema.

The page sends the last 20 messages with each question, so follow-ups such as
*"aur Islamabad ka?"* work. Reloading the page starts a new conversation.

### 2. The direct MCP pages (`/mcp-tester`, `/users-chat`)

These call the MCP endpoints from the browser with no AI in between, which makes them the
quickest way to see the protocol itself.

- **`/mcp-tester`** has three buttons: **List tools** (`tools/list`), **Current weather**,
  and **When will it rain?**. Each shows a formatted result and the raw JSON-RPC response.
- **`/users-chat`** has three fields (Name or email, Starts with, Limit) that map straight
  to the users tool's arguments.

Compare `/users-chat` with `/ai-chat`: same tool, but here you fill in the arguments yourself.

### 3. From an AI assistant (Claude Code and others)

The repo ships a `.mcp.json`, so **Claude Code picks up the weather server automatically**
when you open the project. To add the users server, add this to `mcpServers` in
`.mcp.json`:

```json
"users": {
    "command": "php",
    "args": ["artisan", "mcp:start", "users"]
}
```

For other clients, register a local server with `command: php` and
`args: ["artisan", "mcp:start", "weather"]` (or `"users"`).

> Do not run `php artisan mcp:start weather` by hand in a terminal. It waits on stdin and
> will appear to hang. It is meant to be launched by an MCP client.

The official inspector is the best debugging UI:

```bash
php artisan mcp:inspector weather       # local (stdio) server
php artisan mcp:inspector mcp/weather   # HTTP server
php artisan mcp:inspector users
php artisan mcp:inspector mcp/users
```

### 4. Over HTTP

```bash
curl -X POST http://localhost:8000/mcp/weather \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/call",
       "params":{"name":"rain-forecast-tool","arguments":{"location":"Narowal"}}}'

curl -X POST http://localhost:8000/mcp/users \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/call",
       "params":{"name":"user-information-tool","arguments":{"starts_with":"A","limit":5}}}'
```

Send `{"jsonrpc":"2.0","id":1,"method":"tools/list"}` to either endpoint to list its tools.

> Opening `/mcp/weather` in the address bar will not work. Tool calls are **POST**
> JSON-RPC; a browser address bar sends GET.

---

## How the code fits together

```
routes/ai.php                              Registers both MCP servers (local + web)
├── app/Mcp/Servers/WeatherServer.php
│   ├── app/Mcp/Tools/CurrentWeatherTool.php
│   └── app/Mcp/Tools/RainForecastTool.php
│       ├── app/Actions/ResolveLocation.php     place name → coordinates
│       └── app/Support/WeatherConditions.php   WMO codes → plain English
└── app/Mcp/Servers/UserServer.php
    └── app/Mcp/Tools/UserInformationTool.php
            └── database/seeders/DummyUsersSeeder.php

routes/web.php                             Pages, plus POST /ai-chat
└── app/Http/Controllers/AiChatController.php
    └── app/Ai/Agents/AssistantAgent.php   The Gemini agent; reuses the three MCP tools
        └── config/ai.php                  Providers, API keys, failover models
```

### An MCP tool

A tool is a class with a description, a schema, and a `handle` method:

```php
#[Description('Predicts when rain is next expected at a location.')]  // what it is for
class RainForecastTool extends Tool
{
    public function handle(Request $request): Response { /* what it does */ }

    public function schema(JsonSchema $schema): array  { /* what it accepts */ }
}
```

The `#[Description]` and `schema()` are **the only documentation the AI ever sees**. A
vague description means the model misuses the tool or skips it. This matters in practice:
the users tool originally took one free-text `question` and guessed the intent with a regex,
so *"first 5 users"* returned nothing. Splitting it into `search`, `starts_with`, and
`limit` let the model fill in exact values instead.

### The AI agent

[`AssistantAgent`](app/Ai/Agents/AssistantAgent.php) is a normal PHP class. Its `tools()`
method returns the **same MCP tool classes**; the AI SDK wraps them and runs them in-process,
so nothing is duplicated:

```php
#[Provider(['gemini-lite', 'gemini'])]   // try these in order
#[MaxSteps(5)]                           // at most 5 model/tool round trips
#[Timeout(10)]                           // give up on a provider after 10 seconds
class AssistantAgent implements Agent, Conversational, HasTools
{
    public function tools(): iterable
    {
        return [
            app(CurrentWeatherTool::class),
            app(RainForecastTool::class),
            app(UserInformationTool::class),
        ];
    }
}
```

The controller calls it with one line:

```php
$response = (new AssistantAgent($history))->prompt($message);
```

That `prompt()` call is where the request to Gemini happens. The SDK sends the message and
the tool list, runs any tool the model asks for, sends the result back, and returns the
final text in `$response->text`. The tools the model used are in `$response->toolCalls`.

### Provider failover

`config/ai.php` defines two Gemini entries that share `GEMINI_API_KEY`:

| Provider name | Model | Role |
|---|---|---|
| `gemini-lite` | `gemini-3.1-flash-lite` | Tried first |
| `gemini` | `gemini-3.8-flash` | Backup |

If the first provider is overloaded, rate limited, or does not answer within 10 seconds,
the SDK moves to the next one automatically.

### Switching to another provider

Only the `#[Provider]` line changes. For example, for Anthropic, set `ANTHROPIC_API_KEY` in
`.env` and use either of these:

```php
#[Provider('anthropic')]                              // Anthropic only
#[Provider(['gemini-lite', 'gemini', 'anthropic'])]   // Anthropic as a last resort
```

Adding a key alone does **not** switch providers, because the attribute names them
explicitly. The tools, controller, page, and tests stay the same.

---

## Tests

```bash
composer test
```

No test touches the network or spends API quota:

- Weather tests fake Open-Meteo with `Http::fake()`.
- AI chat tests fake the agent:

  ```php
  AssistantAgent::fake(['Lahore mein abhi 31°C hai.']);

  $this->postJson(route('ai-chat.send'), ['message' => 'Lahore ka mausam?'])
      ->assertJsonPath('reply', 'Lahore mein abhi 31°C hai.');

  AssistantAgent::assertPrompted('Lahore ka mausam?');
  ```

- MCP tools can be invoked directly:

  ```php
  UserServer::tool(UserInformationTool::class, ['limit' => 2])
      ->assertSee('Found 2 matching dummy users.');
  ```

Code style:

```bash
./vendor/bin/pint
```

---

## Things worth knowing before you build on this

**Gemini's free tier has small daily limits.** At the time of writing, `gemini-3.8-flash`
allowed 20 requests per day on the free tier, and each chat question uses at least two
requests (one to choose the tool, one to write the answer). When every provider in the
failover list is exhausted or overloaded, `/ai-chat` shows *"The AI provider could not answer
right now"*. The real cause is in `storage/logs`. Check your current limits in Google AI
Studio.

**PHP's time limit is 30 seconds by default.** A slow provider plus a tool call can exceed it
and produce an HTTP 504. That is why the agent uses a 10-second timeout per provider.

**None of the endpoints have authentication.** `/mcp/weather`, `/mcp/users`, and
`POST /ai-chat` are open, protected only by rate limits (`throttle:60,1` for MCP,
`throttle:20,1` for the chat). The AI chat spends your API quota, so protect it before
deploying:

```php
Route::post('/ai-chat', AiChatController::class)->middleware(['auth', 'throttle:20,1']);
Mcp::web('/mcp/weather', WeatherServer::class)->middleware(['auth:sanctum']);
```

Do not point the users tool at real account data without adding authorization first.

**`/mcp-tester`, `/users-chat`, and `/ai-chat` are development pages.** Remove or protect
them before going to production.

**Rebuild the CSS after adding new Tailwind classes.** Pages use compiled Tailwind. If a new
class has no effect, run `npm run dev` while editing or `npm run build` once.

**Tool names are derived from class names.** `RainForecastTool` becomes
`rain-forecast-tool`. Set `protected string $name = 'rain-forecast';` to override it.

**Rain is reported at 50% probability or above.** Change `protected int $threshold = 50;`
in `RainForecastTool` to adjust.

---

## Where to go next

- Give the agent conversation memory stored in the database with the AI SDK's
  `RemembersConversations` trait (publish its migrations first).
- Stream replies word by word with `->stream()` instead of `->prompt()`.
- Add MCP **resources** and **prompts**: `php artisan make:mcp-resource`,
  `php artisan make:mcp-prompt`.
- Add a multi-day temperature tool and ask the AI chat to compare two cities.

Docs:

- [Laravel AI SDK](https://laravel.com/docs/ai-sdk)
- [Laravel MCP](https://laravel.com/docs/mcp)
- [Model Context Protocol specification](https://modelcontextprotocol.io)
- [Open-Meteo API](https://open-meteo.com/en/docs)

---

## License

MIT.
