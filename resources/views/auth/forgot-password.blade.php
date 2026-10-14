@extends('layouts.auth')

@section('title', 'Forgot password')
@section('heading', 'Forgot your password?')
@section('subheading', "Enter your email address and we'll send you a link to choose a new password.")

@section('form')
    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <x-form-input label="Email" name="email" type="email" :value="old('email')" required autofocus autocomplete="username" />

        <x-button class="w-full">Email reset link</x-button>
    </form>
@endsection

@section('footer')
    Remembered it?
    <a href="{{ route('login') }}" class="font-semibold text-violet-600 hover:text-violet-700">Back to log in</a>
@endsection
