@extends('layouts.auth')

@section('title', 'Create account')
@section('heading', 'Create your account')
@section('subheading', "We'll send you a link to verify your email address.")

@section('form')
    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <x-form-input label="Name" name="name" :value="old('name')" required autofocus autocomplete="name" />

        <x-form-input label="Email" name="email" type="email" :value="old('email')" required autocomplete="username" />

        <x-form-input label="Password" name="password" type="password" required autocomplete="new-password" />

        <x-form-input label="Confirm password" name="password_confirmation" type="password" required autocomplete="new-password" />

        <x-button class="w-full">Create account</x-button>
    </form>
@endsection

@section('footer')
    Already registered?
    <a href="{{ route('login') }}" class="font-semibold text-violet-600 hover:text-violet-700">Log in</a>
@endsection
