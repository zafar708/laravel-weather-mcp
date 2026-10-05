<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MCP Weather Tester</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen py-10 px-4">
    <div class="mx-auto max-w-3xl space-y-6">

        <header>
            <h1 class="text-2xl font-bold text-slate-900">MCP Weather Tester</h1>
            <p class="mt-1 text-sm text-slate-600">
                Sends real JSON-RPC requests to
                <code class="rounded bg-slate-200 px-1.5 py-0.5 text-xs">POST {{ url('/mcp/weather') }}</code>
            </p>
        </header>

        <div class="rounded-lg bg-white p-5 shadow-sm space-y-4">
            <div class="flex flex-wrap gap-3">
                <input id="location" type="text" value="Narowal" placeholder="City name"
                       class="flex-1 min-w-48 rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                <select id="units" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <option value="celsius">Celsius</option>
                    <option value="fahrenheit">Fahrenheit</option>
                </select>
            </div>

            <div class="flex flex-wrap gap-2">
                <button onclick="listTools()"
                        class="rounded-md bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                    1. List tools
                </button>
                <button onclick="getWeather()"
                        class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    2. Current weather
                </button>
                <button onclick="getRain()"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    3. When will it rain?
                </button>
            </div>
        </div>

        <div id="summary" class="hidden rounded-lg bg-white p-5 shadow-sm"></div>

        <div>
            <h2 class="mb-2 text-sm font-semibold text-slate-700">Raw JSON-RPC response</h2>
            <pre id="output" class="overflow-x-auto rounded-lg bg-slate-900 p-4 text-xs leading-relaxed text-emerald-300">Click a button above to begin.</pre>
        </div>
    </div>

<script>
const ENDPOINT = @json(url('/mcp/weather'));
let requestId = 0;

function render(data) {
    document.getElementById('output').textContent =
        typeof data === 'string' ? data : JSON.stringify(data, null, 2);
}

function showSummary(html) {
    const box = document.getElementById('summary');
    box.innerHTML = html;
    box.classList.remove('hidden');
}

function showError(message) {
    showSummary('<p class="text-sm font-medium text-red-600">' + message + '</p>');
}

async function call(method, params = {}) {
    document.getElementById('output').textContent = 'Loading...';
    document.getElementById('summary').classList.add('hidden');

    try {
        const response = await fetch(ENDPOINT, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json, text/event-stream',
            },
            body: JSON.stringify({ jsonrpc: '2.0', id: ++requestId, method, params }),
        });

        const text = await response.text();

        // A streamable HTTP server may answer with SSE framing.
        const line = text.split('\n').find(l => l.startsWith('data: '));
        const payload = line ? line.slice(6) : text;

        try {
            return JSON.parse(payload);
        } catch {
            render(text);
            showError('The server did not return JSON. See the raw response below.');
            return null;
        }
    } catch (error) {
        render(String(error));
        showError('Request failed: ' + error.message);
        return null;
    }
}

/**
 * Pull the tool payload out of a JSON-RPC result, or report why we cannot.
 */
function toolPayload(data) {
    if (data.error || data.result?.isError) {
        showError(data.error?.message ?? data.result?.content?.[0]?.text ?? 'Request failed');
        return null;
    }

    try {
        return JSON.parse(data.result.content[0].text);
    } catch {
        showError('Could not parse the tool response. See the raw JSON below.');
        return null;
    }
}

function location_() {
    return document.getElementById('location').value;
}

async function listTools() {
    const data = await call('tools/list');
    if (!data) return;
    render(data);

    const tools = data.result?.tools ?? [];

    showSummary(
        '<h3 class="mb-2 font-semibold text-slate-900">Tools exposed by this server</h3>' +
        tools.map(t =>
            '<div class="mb-1 text-sm text-slate-700"><code class="rounded bg-slate-100 px-1">' +
            t.name + '</code> &mdash; ' + (t.description ?? '') + '</div>'
        ).join('')
    );
}

async function getWeather() {
    const data = await call('tools/call', {
        name: 'current-weather-tool',
        arguments: {
            location: location_(),
            units: document.getElementById('units').value,
        },
    });
    if (!data) return;
    render(data);

    const w = toolPayload(data);
    if (!w) return;

    const symbol = w.units === 'celsius' ? '&deg;C' : '&deg;F';

    showSummary(`
        <div class="flex items-baseline justify-between">
            <h3 class="text-lg font-semibold text-slate-900">${w.location}</h3>
            <span class="text-3xl font-bold text-blue-600">${w.temperature}${symbol}</span>
        </div>
        <p class="mt-1 text-sm capitalize text-slate-600">${w.conditions}</p>
        <div class="mt-4 grid grid-cols-3 gap-4 border-t border-slate-200 pt-4 text-sm">
            <div><div class="text-slate-500">Humidity</div><div class="font-medium">${w.humidity}%</div></div>
            <div><div class="text-slate-500">Wind</div><div class="font-medium">${w.wind_speed} km/h</div></div>
            <div><div class="text-slate-500">Observed</div><div class="font-medium">${w.observed_at}</div></div>
        </div>
    `);
}

async function getRain() {
    const data = await call('tools/call', {
        name: 'rain-forecast-tool',
        arguments: { location: location_() },
    });
    if (!data) return;
    render(data);

    const f = toolPayload(data);
    if (!f) return;

    const next = f.next_rain
        ? `<div class="rounded-md bg-indigo-50 p-3">
               <div class="text-sm font-medium text-indigo-900">${f.next_rain.readable}</div>
               <div class="mt-1 text-xs text-indigo-700">
                   ${f.next_rain.probability}% chance &middot; ${f.next_rain.amount_mm}mm &middot;
                   ${f.next_rain.conditions} &middot; in ${f.next_rain.hours_from_now}h
               </div>
           </div>`
        : '<div class="rounded-md bg-slate-50 p-3 text-sm text-slate-600">No rain expected in this period.</div>';

    const rows = f.daily.map(d => `
        <div class="flex items-center gap-3 text-sm">
            <div class="w-24 shrink-0 text-slate-600">${d.readable}</div>
            <div class="h-2 flex-1 overflow-hidden rounded bg-slate-200">
                <div class="h-full bg-indigo-500" style="width:${Math.max(2, d.chance)}%"></div>
            </div>
            <div class="w-10 shrink-0 text-right font-medium">${d.chance}%</div>
            <div class="w-16 shrink-0 text-right text-slate-500">${d.amount_mm}mm</div>
        </div>
    `).join('');

    showSummary(`
        <h3 class="text-lg font-semibold text-slate-900">${f.location}</h3>
        <p class="mb-3 text-xs text-slate-500">Times shown in ${f.timezone}</p>
        ${next}
        <div class="mt-4 space-y-2 border-t border-slate-200 pt-4">${rows}</div>
    `);
}
</script>
</body>
</html>
