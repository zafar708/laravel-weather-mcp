<x-mail::message>
# Hi {{ $name }},

Thanks for signing up for {{ config('app.name') }}! Please confirm that this is your email address by clicking the button below.

<x-mail::button :url="$url" color="primary">
Verify email address
</x-mail::button>

This link expires in {{ $expiresInMinutes }} minutes. If you did not create an account, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}

<x-slot:subcopy>
If the button doesn't work, copy and paste this URL into your browser: <span class="break-all">[{{ $url }}]({{ $url }})</span>
</x-slot:subcopy>
</x-mail::message>
