<x-mail::message>
# Welcome aboard, {{ $user->name }}!

Your email address is verified and your {{ config('app.name') }} account is ready to use.

From your dashboard you can chat with the AI assistant, explore user data, and test the MCP weather tools.

<x-mail::button :url="$dashboardUrl" color="primary">
Go to dashboard
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
