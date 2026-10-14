@extends('layouts.app')

@section('body-class', 'min-h-screen bg-slate-100 font-sans text-slate-900 antialiased')

@section('content')
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-10">
        <a href="{{ url('/') }}" class="mb-8 flex items-center gap-3">
            <span class="flex size-11 items-center justify-center rounded-2xl bg-violet-600 text-lg font-bold text-white">
                {{ str(config('app.name'))->substr(0, 1)->upper() }}
            </span>
            <span class="text-lg font-semibold text-slate-900">{{ config('app.name') }}</span>
        </a>

        <div class="w-full max-w-md rounded-3xl bg-white p-8 shadow-xl sm:p-10">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">@yield('heading')</h1>

            @hasSection('subheading')
                <p class="mt-2 text-sm leading-6 text-slate-600">@yield('subheading')</p>
            @endif

            <x-status-alert class="mt-6" />

            <div class="mt-6">
                @yield('form')
            </div>
        </div>

        @hasSection('footer')
            <p class="mt-6 text-sm text-slate-600">@yield('footer')</p>
        @endif
    </div>
@endsection
