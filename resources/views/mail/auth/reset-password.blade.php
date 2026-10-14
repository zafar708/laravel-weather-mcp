<x-mail::message>
# Hi {{ $name }},

We received a request to reset the password for your {{ config('app.name') }} account. Click the button below to choose a new one.

<x-mail::button :url="$url" color="primary">
Reset password
</x-mail::button>

This link expires in {{ $expiresInMinutes }} minutes. If you did not request a password reset, no action is needed and your password will stay the same.

Thanks,<br>
{{ config('app.name') }}

<x-slot:subcopy>
If the button doesn't work, copy and paste this URL into your browser: <span class="break-all">[{{ $url }}]({{ $url }})</span>
</x-slot:subcopy>
</x-mail::message>
