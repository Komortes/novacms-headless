@props([
    'eyebrow'       => null,
    'title'         => null,
    'description'   => null,
    'badge'         => null,
    'tone'          => 'default',
    'padding'       => 'p-5',
])

@php
$tones = [
    'default' => [
        'section' => 'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900',
        'badge'   => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
        'bar'     => 'bg-slate-300 dark:bg-slate-700',
    ],
    'rose' => [
        'section' => 'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900',
        'badge'   => 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
        'bar'     => 'bg-rose-400',
    ],
    'amber' => [
        'section' => 'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900',
        'badge'   => 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
        'bar'     => 'bg-amber-400',
    ],
    'sky' => [
        'section' => 'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900',
        'badge'   => 'bg-sky-50 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300',
        'bar'     => 'bg-sky-400',
    ],
    'indigo' => [
        'section' => 'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900',
        'badge'   => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300',
        'bar'     => 'bg-indigo-500',
    ],
    'emerald' => [
        'section' => 'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900',
        'badge'   => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
        'bar'     => 'bg-emerald-500',
    ],
];

$tc = $tones[$tone] ?? $tones['default'];
$hasHeader = filled($eyebrow) || filled($title) || filled($description) || filled($badge) || isset($headerActions);
@endphp

<section {{ $attributes->class(['relative overflow-hidden rounded-2xl border shadow-sm', $tc['section']]) }}>
    {{-- Colored top accent line --}}
    <div class="absolute inset-x-0 top-0 h-[2px] {{ $tc['bar'] }}"></div>

    <div class="{{ $padding }} pt-6">
        @if ($hasHeader)
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    @if (filled($eyebrow))
                        <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">{{ $eyebrow }}</p>
                    @endif

                    @if (filled($title))
                        <h3 class="mt-1 text-base font-semibold tracking-tight text-slate-950 dark:text-slate-100">{{ $title }}</h3>
                    @endif

                    @if (filled($description))
                        <p class="mt-1.5 max-w-prose text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $description }}</p>
                    @endif
                </div>

                @if (filled($badge) || isset($headerActions))
                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                        @if (filled($badge))
                            <span @class(['rounded-md px-2 py-0.5 text-xs font-semibold', $tc['badge']])>{{ $badge }}</span>
                        @endif

                        @isset($headerActions)
                            {{ $headerActions }}
                        @endisset
                    </div>
                @endif
            </div>
        @endif

        @if (! $slot->isEmpty())
            <div class="{{ $hasHeader ? 'mt-5' : '' }}">
                {{ $slot }}
            </div>
        @endif
    </div>
</section>
