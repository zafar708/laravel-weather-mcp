@extends('layouts.auth')

@section('title', 'Verify email')
@section('heading', 'Check your inbox')
@section('subheading')
    We sent a verification link to <span class="font-semibold text-slate-900">{{ auth()->user()->email }}</span>.
    Click it to activate your account.
@endsection

@section('form')
    <div class="space-y-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-button class="w-full">Resend verification email</x-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-button variant="secondary" class="w-full">Log out</x-button>
        </form>
    </div>
@endsection

@section('footer')
    Wrong email address?
    <a href="{{ route('profile.edit') }}" class="font-semibold text-violet-600 hover:text-violet-700">Update it</a>
@endsection
