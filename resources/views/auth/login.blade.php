@extends('layouts.auth')

@section('title', 'Log in')
@section('heading', 'Welcome back')
@section('subheading', 'Log in to your account to continue.')

@section('form')
    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <x-form-input label="Email" name="email" type="email" :value="old('email')" required autofocus autocomplete="username" />

        <div>
            <x-form-input label="Password" name="password" type="password" required autocomplete="current-password" />

            <div class="mt-2 text-right">
                <a href="{{ route('password.request') }}" class="text-sm font-medium text-violet-600 hover:text-violet-700">
                    Forgot your password?
                </a>
            </div>
        </div>

        <label class="flex items-center gap-2.5 text-sm text-slate-600">
            <input type="checkbox" name="remember" value="1" @checked(old('remember')) class="size-4 accent-violet-600">
            Remember me
        </label>

        <x-button class="w-full">Log in</x-button>
    </form>
@endsection

@section('footer')
    Don't have an account?
    <a href="{{ route('register') }}" class="font-semibold text-violet-600 hover:text-violet-700">Create one</a>
@endsection
