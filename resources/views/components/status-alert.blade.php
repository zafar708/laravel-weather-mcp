@if (session('status'))
    <div {{ $attributes->class('rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800') }} role="status">
        {{ session('status') }}
    </div>
@endif
