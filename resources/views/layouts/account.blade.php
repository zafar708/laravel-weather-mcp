@extends('layouts.app')

@section('body-class', 'min-h-screen bg-slate-100 font-sans text-slate-900 antialiased')

@section('content')
    <nav class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex h-16 max-w-5xl items-center justify-between gap-4 px-4 sm:px-6">
            <div class="flex items-center gap-6">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-violet-600 font-bold text-white">
                        {{ str(config('app.name'))->substr(0, 1)->upper() }}
                    </span>
                    <span class="hidden font-semibold text-slate-900 sm:inline">{{ config('app.name') }}</span>
                </a>

                <div class="flex items-center gap-1 text-sm font-medium">
                    @foreach (['dashboard' => 'Dashboard', 'profile.edit' => 'Account'] as $routeName => $label)
                        <a
                            href="{{ route($routeName) }}"
                            @class([
                                'rounded-lg px-3 py-2 transition',
                                'bg-violet-50 text-violet-700' => request()->routeIs($routeName),
                                'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! request()->routeIs($routeName),
                            ])
                        >
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span class="hidden max-w-40 truncate text-sm text-slate-600 sm:inline">{{ auth()->user()->name }}</span>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-button variant="secondary">Log out</x-button>
                </form>
            </div>
        </div>
    </nav>

    <main class="mx-auto max-w-5xl px-4 py-10 sm:px-6">
        @yield('page')
    </main>
@endsection
