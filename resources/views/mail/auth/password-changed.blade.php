<x-mail::message>
# Hi {{ $name }},

The password for your {{ config('app.name') }} account was changed on **{{ $changedAt }}**.

If you made this change, you don't need to do anything.

<x-mail::panel>
**Didn't change your password?** Someone else may have access to your account. Reset your password right away to secure it.
</x-mail::panel>

<x-mail::button :url="$resetUrl" color="error">
Reset my password
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
