@props(['variant' => 'primary'])

<button {{ $attributes->merge(['type' => 'submit'])->class([
    'inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold transition focus:outline-hidden focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 cursor-pointer',
    'bg-violet-600 text-white shadow-xs hover:bg-violet-700 focus-visible:ring-violet-500' => $variant === 'primary',
    'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 focus-visible:ring-slate-400' => $variant === 'secondary',
    'bg-red-600 text-white shadow-xs hover:bg-red-700 focus-visible:ring-red-500' => $variant === 'danger',
]) }}>
    {{ $slot }}
</button>
