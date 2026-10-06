@extends('layouts.app')

@section('title', 'User Data Chat')
@section('body-class', 'min-h-screen bg-slate-100 font-sans text-slate-900')

@section('content')
    <div class="mx-auto flex h-dvh max-w-5xl flex-col overflow-hidden bg-white shadow-xl md:my-6 md:h-[calc(100dvh-3rem)] md:rounded-3xl">
        <header class="flex items-center gap-3 border-b border-slate-200 px-5 py-4 sm:px-8">
            <div class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-indigo-600 text-lg font-bold text-white">
                U
            </div>
            <div>
                <h1 class="text-lg font-semibold">User Data Assistant</h1>
                <p class="text-sm text-slate-500">Ask about the 50 sample users</p>
            </div>
            <span class="ml-auto hidden rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 sm:inline-flex">
                MCP connected
            </span>
        </header>

        <main id="messages" class="flex-1 space-y-6 overflow-y-auto px-4 py-6 sm:px-8" aria-live="polite">
            <section id="welcome" class="mx-auto mt-12 max-w-lg text-center">
                <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-indigo-50 text-2xl text-indigo-600">
                    ✦
                </div>
                <h2 class="text-xl font-semibold">What would you like to know?</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">
                    Search the sample users by name or email, by the first letters of the name, or limit how many are returned. The filters are sent straight to the Users MCP tool, with no AI in between.
                </p>
                <p class="mt-4 text-xs text-slate-400">Leave every field empty to list all users. For plain-language questions, use the AI chat.</p>
            </section>
        </main>

        <form id="chat-form" class="border-t border-slate-200 bg-white px-4 py-4 sm:px-8">
            <div class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-300 bg-white p-2 shadow-sm transition focus-within:border-indigo-400 focus-within:ring-4 focus-within:ring-indigo-100">
                <label class="flex min-w-48 flex-1 flex-col gap-1 px-2 text-xs font-medium text-slate-500">
                    Name or email
                    <input id="search" name="search" type="text" maxlength="100" placeholder="e.g. Zafar"
                           class="h-9 border-0 bg-transparent text-sm text-slate-900 outline-none placeholder:text-slate-400">
                </label>
                <label class="flex w-28 flex-col gap-1 px-2 text-xs font-medium text-slate-500">
                    Starts with
                    <input id="starts-with" name="starts_with" type="text" maxlength="20" placeholder="e.g. Z"
                           class="h-9 border-0 bg-transparent text-sm text-slate-900 outline-none placeholder:text-slate-400">
                </label>
                <label class="flex w-20 flex-col gap-1 px-2 text-xs font-medium text-slate-500">
                    Limit
                    <input id="limit" name="limit" type="number" min="1" max="50" placeholder="50"
                           class="h-9 border-0 bg-transparent text-sm text-slate-900 outline-none placeholder:text-slate-400">
                </label>
                <button id="send-button" type="submit"
                        class="inline-flex h-11 shrink-0 items-center justify-center rounded-xl bg-indigo-600 px-4 text-sm font-medium text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-200 disabled:cursor-not-allowed disabled:bg-slate-300">
                    Search
                </button>
            </div>
            <p class="mt-2 text-center text-xs text-slate-400">
                Results come from dummy records only.
            </p>
        </form>
    </div>

    <script>
        const endpoint = @json(url('/mcp/users'));
        const chatForm = document.getElementById('chat-form');
        const searchInput = document.getElementById('search');
        const startsWithInput = document.getElementById('starts-with');
        const limitInput = document.getElementById('limit');
        const sendButton = document.getElementById('send-button');
        const messages = document.getElementById('messages');
        const welcome = document.getElementById('welcome');
        let requestId = 0;

        function addMessage(role, text, users = [], isError = false) {
            const row = document.createElement('article');
            row.className = role === 'user' ? 'flex justify-end' : 'flex justify-start';

            const bubble = document.createElement('div');
            bubble.className = role === 'user'
                ? 'max-w-[85%] whitespace-pre-wrap rounded-2xl rounded-br-md bg-indigo-600 px-4 py-3 text-sm leading-6 text-white sm:max-w-[75%]'
                : `max-w-[95%] rounded-2xl rounded-bl-md px-4 py-3 text-sm leading-6 sm:max-w-[85%] ${isError ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-800'}`;

            const reply = document.createElement('p');
            reply.textContent = text;
            bubble.append(reply);

            if (users.length > 0) {
                const userList = document.createElement('ul');
                userList.className = 'mt-3 grid gap-2 sm:grid-cols-2';

                for (const user of users) {
                    const item = document.createElement('li');
                    item.className = 'rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700';

                    const name = document.createElement('p');
                    name.className = 'font-medium';
                    name.textContent = `${user.name} (ID: ${user.id})`;

                    const email = document.createElement('p');
                    email.className = 'break-all text-xs text-slate-500';
                    email.textContent = user.email;

                    item.append(name, email);
                    userList.append(item);
                }

                bubble.append(userList);
            }

            row.append(bubble);
            messages.append(row);
            messages.scrollTop = messages.scrollHeight;

            return row;
        }

        async function searchUsers(filters) {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json, text/event-stream',
                },
                body: JSON.stringify({
                    jsonrpc: '2.0',
                    id: ++requestId,
                    method: 'tools/call',
                    params: {
                        name: 'user-information-tool',
                        arguments: filters,
                    },
                }),
            });

            const responseText = await response.text();

            if (!response.ok) {
                throw new Error(`The MCP server returned HTTP ${response.status}.`);
            }

            const event = responseText.split(/\r?\n/).find(line => line.startsWith('data: '));
            const payload = JSON.parse(event ? event.slice(6) : responseText);

            if (payload.error || payload.result?.isError) {
                throw new Error(payload.error?.message ?? payload.result?.content?.[0]?.text ?? 'The MCP request failed.');
            }

            const content = payload.result?.content?.find(item => item.type === 'text')?.text;

            if (!content) {
                throw new Error('The MCP server returned an empty response.');
            }

            return JSON.parse(content);
        }

        chatForm.addEventListener('submit', async event => {
            event.preventDefault();

            if (sendButton.disabled) {
                return;
            }

            const filters = {};
            const labels = [];

            if (searchInput.value.trim()) {
                filters.search = searchInput.value.trim();
                labels.push(`Name or email: ${filters.search}`);
            }

            if (startsWithInput.value.trim()) {
                filters.starts_with = startsWithInput.value.trim();
                labels.push(`Starts with: ${filters.starts_with}`);
            }

            if (limitInput.value) {
                filters.limit = Number(limitInput.value);
                labels.push(`Limit: ${filters.limit}`);
            }

            welcome.hidden = true;
            addMessage('user', labels.length > 0 ? labels.join(' · ') : 'All users');
            sendButton.disabled = true;
            sendButton.textContent = '...';
            const status = addMessage('assistant', 'Searching the sample users...');

            try {
                const result = await searchUsers(filters);
                status.remove();
                addMessage('assistant', result.answer, result.users);
            } catch (error) {
                console.error('User data MCP request failed.', error);
                status.remove();
                addMessage('assistant', error.message || 'Could not get a reply. Please try again.', [], true);
            } finally {
                sendButton.disabled = false;
                sendButton.textContent = 'Search';
                searchInput.focus();
            }
        });
    </script>
@endsection
