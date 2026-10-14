@props([
    'label',
    'name',
    'type' => 'text',
    'value' => null,
    'bag' => 'default',
])

@php
    $id = $attributes->get('id', $bag === 'default' ? $name : "{$bag}_{$name}");
    $hasError = $errors->getBag($bag)->has($name);
@endphp

<div>
    <label for="{{ $id }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>

    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($type !== 'password') value="{{ $value }}" @endif
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}_error" @endif
        {{ $attributes->except('id')->class([
            'mt-1.5 block w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-xs placeholder:text-slate-400 focus:outline-hidden focus:ring-2',
            'border-slate-300 focus:border-violet-500 focus:ring-violet-500/20' => ! $hasError,
            'border-red-400 focus:border-red-500 focus:ring-red-500/20' => $hasError,
        ]) }}
    >

    @error($name, $bag)
        <p id="{{ $id }}_error" class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
