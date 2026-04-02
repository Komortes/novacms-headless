@props([
    'label',
    'value',
    'description' => null,
    'tone' => 'gray',
])

@php
$bars = [
    'gray'    => 'bg-slate-400',
    'amber'   => 'bg-amber-400',
    'sky'     => 'bg-sky-400',
    'rose'    => 'bg-rose-400',
    'emerald' => 'bg-emerald-500',
    'indigo'  => 'bg-indigo-500',
];
$bar = $bars[$tone] ?? $bars['gray'];
@endphp

<article {{ $attributes->class(['relative overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900']) }}>
    {{-- Colored left accent bar --}}
    <div class="absolute inset-y-0 left-0 w-[3px] {{ $bar }}"></div>

    <div class="pl-5 pr-4 py-4">
        <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $label }}</p>
        <p class="mt-2 font-mono text-[2rem] font-semibold leading-none tracking-tight text-slate-950 dark:text-slate-50">{{ $value }}</p>

        @if (filled($description))
            <p class="mt-2 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ $description }}</p>
        @endif
    </div>
</article>
