@props([
    'label',
    'value',
    'description' => null,
    'tone' => 'gray',
])

@php
    $tones = [
        'gray' => 'border-gray-200 text-gray-950 dark:border-gray-800/40 dark:text-gray-100',
        'amber' => 'border-amber-200 text-amber-950 dark:border-amber-800/40 dark:text-amber-100',
        'sky' => 'border-sky-200 text-sky-950 dark:border-sky-800/40 dark:text-sky-100',
        'rose' => 'border-rose-200 text-rose-950 dark:border-rose-800/40 dark:text-rose-100',
        'emerald' => 'border-emerald-200 text-emerald-950 dark:border-emerald-800/40 dark:text-emerald-100',
        'indigo' => 'border-indigo-200 text-indigo-950 dark:border-indigo-800/40 dark:text-indigo-100',
    ];

    $toneClass = $tones[$tone] ?? $tones['gray'];
@endphp

<article {{ $attributes->class(['rounded-2xl border bg-white p-5 shadow-sm dark:bg-gray-900', $toneClass]) }}>
    <p class="text-xs font-semibold uppercase tracking-wide text-current opacity-80">{{ $label }}</p>
    <p class="mt-2 text-3xl font-semibold text-current">{{ $value }}</p>

    @if (filled($description))
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ $description }}</p>
    @endif
</article>
