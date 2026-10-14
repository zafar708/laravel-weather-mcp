@extends('layouts.account')

@section('title', 'Account settings')

@section('page')
    <div class="space-y-6">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Account settings</h1>

        {{-- Profile information --}}
        <section class="rounded-3xl bg-white p-8 shadow-xl">
            <h2 class="text-lg font-semibold text-slate-900">Profile information</h2>
            <p class="mt-1 text-sm text-slate-600">Changing your email address will require you to verify it again.</p>

            <form method="POST" action="{{ route('profile.update') }}" class="mt-6 max-w-xl space-y-5">
                @csrf
                @method('PATCH')

                <x-form-input label="Name" name="name" :value="old('name', $user->name)" required autocomplete="name" />

                <x-form-input label="Email" name="email" type="email" :value="old('email', $user->email)" required autocomplete="username" />

                <div class="flex items-center gap-4">
                    <x-button>Save</x-button>

                    @if (session('status') === 'profile-updated')
                        <p class="text-sm text-emerald-700" role="status">Saved.</p>
                    @endif
                </div>
            </form>

            @unless ($user->hasVerifiedEmail())
                <div class="mt-6 flex max-w-xl flex-wrap items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    <span>Your email address is not verified yet.</span>

                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button type="submit" class="cursor-pointer font-semibold underline hover:text-amber-900">Resend verification email</button>
                    </form>
                </div>
            @endunless
        </section>

        {{-- Password --}}
        <section class="rounded-3xl bg-white p-8 shadow-xl">
            <h2 class="text-lg font-semibold text-slate-900">Change password</h2>
            <p class="mt-1 text-sm text-slate-600">We'll email you whenever your password is changed.</p>

            <form method="POST" action="{{ route('password.update') }}" class="mt-6 max-w-xl space-y-5">
                @csrf
                @method('PUT')

                <x-form-input label="Current password" name="current_password" type="password" bag="updatePassword" required autocomplete="current-password" />

                <x-form-input label="New password" name="password" type="password" bag="updatePassword" required autocomplete="new-password" />

                <x-form-input label="Confirm new password" name="password_confirmation" type="password" bag="updatePassword" required autocomplete="new-password" />

                <div class="flex items-center gap-4">
                    <x-button>Update password</x-button>

                    @if (session('status') === 'password-updated')
                        <p class="text-sm text-emerald-700" role="status">Password updated.</p>
                    @endif
                </div>
            </form>
        </section>

        {{-- Desktop notifications --}}
        <section class="rounded-3xl bg-white p-8 shadow-xl">
            <h2 class="text-lg font-semibold text-slate-900">Desktop notifications</h2>
            <p class="mt-1 max-w-xl text-sm text-slate-600">
                Get an alert in the corner of your screen when your password changes, even when this site isn't open.
                This is set per browser, so enable it on each browser you use.
            </p>

            <div class="mt-6 flex flex-wrap items-center gap-4">
                <x-button
                    type="button"
                    variant="secondary"
                    data-push-toggle
                    data-vapid-key="{{ config('webpush.vapid.public_key') }}"
                    data-url="{{ route('push-subscriptions.store') }}"
                    data-csrf="{{ csrf_token() }}"
                    disabled
                >
                    Checking browser support…
                </x-button>

                <p class="text-sm text-emerald-700" data-push-status role="status"></p>
            </div>
        </section>

        {{-- Delete account --}}
        <section class="rounded-3xl border border-red-100 bg-white p-8 shadow-xl">
            <h2 class="text-lg font-semibold text-red-700">Delete account</h2>
            <p class="mt-1 text-sm text-slate-600">This permanently deletes your account and cannot be undone.</p>

            <details class="group mt-6 max-w-xl" @if ($errors->userDeletion->isNotEmpty()) open @endif>
                <summary class="inline-flex cursor-pointer list-none items-center rounded-xl border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-50 group-open:hidden">
                    Delete my account
                </summary>

                <form method="POST" action="{{ route('profile.destroy') }}" class="space-y-5">
                    @csrf
                    @method('DELETE')

                    <x-form-input label="Enter your password to confirm" name="password" type="password" bag="userDeletion" required autocomplete="current-password" />

                    <x-button variant="danger">Permanently delete account</x-button>
                </form>
            </details>
        </section>
    </div>
@endsection
