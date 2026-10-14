@extends('layouts.auth')

@section('title', 'Reset password')
@section('heading', 'Choose a new password')
@section('subheading', 'Your new password will replace the old one and sign out remembered devices.')

@section('form')
    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <x-form-input label="Email" name="email" type="email" :value="old('email', $email)" required autocomplete="username" />

        <x-form-input label="New password" name="password" type="password" required autofocus autocomplete="new-password" />

        <x-form-input label="Confirm new password" name="password_confirmation" type="password" required autocomplete="new-password" />

        <x-button class="w-full">Reset password</x-button>
    </form>
@endsection
