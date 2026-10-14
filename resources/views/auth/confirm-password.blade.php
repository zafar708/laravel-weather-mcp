@extends('layouts.auth')

@section('title', 'Confirm password')
@section('heading', 'Confirm your password')
@section('subheading', 'This is a protected area. Please confirm your password before continuing.')

@section('form')
    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <x-form-input label="Password" name="password" type="password" required autofocus autocomplete="current-password" />

        <x-button class="w-full">Confirm</x-button>
    </form>
@endsection
