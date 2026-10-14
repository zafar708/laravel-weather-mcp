@extends('layouts.account')

@section('title', 'Dashboard')

@section('page')
    <div class="space-y-6">
        <x-status-alert />

        <section class="rounded-3xl bg-white p-8 shadow-xl">
            <p class="text-sm font-medium text-violet-600">Dashboard</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Welcome, {{ $user->name }}</h1>
            <p class="mt-2 text-sm text-slate-600">
                Signed in as {{ $user->email }} &middot; member since {{ $user->created_at->toFormattedDateString() }}
            </p>
        </section>

        <section>
            <h2 class="mb-3 text-sm font-semibold text-slate-500">Tools</h2>

            <div class="grid gap-4 sm:grid-cols-3">
                @foreach ([
                    ['url' => route('ai-chat'), 'title' => 'AI Assistant', 'description' => 'Chat with the assistant agent.'],
                    ['url' => route('users-chat'), 'title' => 'User Data Chat', 'description' => 'Ask questions about user data.'],
                    ['url' => url('/mcp-tester'), 'title' => 'MCP Tester', 'description' => 'Send requests to the weather MCP server.'],
                ] as $tool)
                    <a href="{{ $tool['url'] }}" class="rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-violet-300 hover:shadow-md">
                        <h3 class="font-semibold text-slate-900">{{ $tool['title'] }}</h3>
                        <p class="mt-1 text-sm text-slate-600">{{ $tool['description'] }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    </div>
@endsection
