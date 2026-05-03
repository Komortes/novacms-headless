@props([
    'label',
    'value',
    'description' => null,
    'tone' => 'gray',
])

@php
    $tones = [
        'gray' => [
            'section' => 'border-gray-200 text-gray-950 dark:border-gray-800/40 dark:text-gray-100',
            'soft' => 'bg-gray-300/20',
        ],
        'amber' => [
            'section' => 'border-amber-200 text-amber-950 dark:border-amber-800/40 dark:text-amber-100',
            'soft' => 'bg-amber-300/25',
        ],
        'sky' => [
            'section' => 'border-sky-200 text-sky-950 dark:border-sky-800/40 dark:text-sky-100',
            'soft' => 'bg-sky-300/25',
        ],
        'rose' => [
            'section' => 'border-rose-200 text-rose-950 dark:border-rose-800/40 dark:text-rose-100',
            'soft' => 'bg-rose-300/25',
        ],
        'emerald' => [
            'section' => 'border-emerald-200 text-emerald-950 dark:border-emerald-800/40 dark:text-emerald-100',
            'soft' => 'bg-emerald-300/25',
        ],
        'indigo' => [
            'section' => 'border-indigo-200 text-indigo-950 dark:border-indigo-800/40 dark:text-indigo-100',
            'soft' => 'bg-indigo-300/25',
        ],
    ];

    $toneClass = $tones[$tone] ?? $tones['gray'];
@endphp

<article {{ $attributes->class(['relative overflow-hidden rounded-[1rem] border bg-white px-4 py-3 shadow-sm ring-1 ring-white/50 transition duration-200 dark:bg-gray-900 dark:ring-white/5', $toneClass['section']]) }}>
    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-white/0 via-white/70 to-white/0 opacity-70 dark:via-white/10"></div>
    <div class="relative">
        <p class="text-xs font-semibold uppercase tracking-wide text-current opacity-80">{{ $label }}</p>
        <p class="mt-1 text-3xl font-semibold leading-none text-current">{{ $value }}</p>

        @if (filled($description))
            <p class="mt-2 truncate text-sm text-gray-600 dark:text-gray-300">{{ $description }}</p>
        @endif
    </div>
</article>
