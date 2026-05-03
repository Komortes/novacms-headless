@props([
    'eyebrow' => null,
    'title' => null,
    'description' => null,
    'badge' => null,
    'tone' => 'default',
    'padding' => 'p-5',
])

@php
    $tones = [
        'default' => [
            'section' => 'border-gray-200 bg-white/95 dark:border-gray-800 dark:bg-gray-900/95',
            'badge' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
            'accent' => 'from-gray-200 via-gray-300 to-transparent dark:from-gray-700 dark:via-gray-600',
        ],
        'rose' => [
            'section' => 'border-rose-200 bg-gradient-to-br from-white to-rose-50/80 dark:border-rose-800/40 dark:from-gray-900 dark:to-rose-950/10',
            'badge' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-200',
            'accent' => 'from-rose-300 via-rose-400 to-transparent dark:from-rose-700 dark:via-rose-500',
        ],
        'amber' => [
            'section' => 'border-amber-200 bg-gradient-to-br from-white to-amber-50/80 dark:border-amber-800/40 dark:from-gray-900 dark:to-amber-950/10',
            'badge' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-200',
            'accent' => 'from-amber-300 via-amber-400 to-transparent dark:from-amber-700 dark:via-amber-500',
        ],
        'sky' => [
            'section' => 'border-sky-200 bg-gradient-to-br from-white to-sky-50/80 dark:border-sky-800/40 dark:from-gray-900 dark:to-sky-950/10',
            'badge' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-200',
            'accent' => 'from-sky-300 via-sky-400 to-transparent dark:from-sky-700 dark:via-sky-500',
        ],
        'indigo' => [
            'section' => 'border-indigo-200 bg-gradient-to-br from-white to-indigo-50/80 dark:border-indigo-800/40 dark:from-gray-900 dark:to-indigo-950/10',
            'badge' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-200',
            'accent' => 'from-indigo-300 via-indigo-400 to-transparent dark:from-indigo-700 dark:via-indigo-500',
        ],
        'emerald' => [
            'section' => 'border-emerald-200 bg-gradient-to-br from-white to-emerald-50/80 dark:border-emerald-800/40 dark:from-gray-900 dark:to-emerald-950/10',
            'badge' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200',
            'accent' => 'from-emerald-300 via-emerald-400 to-transparent dark:from-emerald-700 dark:via-emerald-500',
        ],
    ];

    $toneClasses = $tones[$tone] ?? $tones['default'];
@endphp

<section {{ $attributes->class(['relative isolate overflow-hidden rounded-[1.5rem] border shadow-sm', $toneClasses['section'], $padding]) }}>
    <div @class([
        'absolute inset-x-0 top-0 h-px bg-gradient-to-r',
        $toneClasses['accent'],
    ])></div>
    <div class="pointer-events-none absolute -right-10 top-0 h-28 w-28 rounded-full bg-white/30 blur-3xl dark:bg-white/5"></div>

    @if (filled($eyebrow) || filled($title) || filled($description) || filled($badge) || isset($headerActions))
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                @if (filled($eyebrow))
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">{{ $eyebrow }}</p>
                @endif

                @if (filled($title))
                    <h3 class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $title }}</h3>
                @endif

                @if (filled($description))
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ $description }}</p>
                @endif
            </div>

            @if (filled($badge) || isset($headerActions))
                <div class="flex flex-wrap items-center gap-2">
                    @if (filled($badge))
                        <div @class([
                            'rounded-full px-3 py-1 text-xs font-medium',
                            $toneClasses['badge'],
                        ])>
                            {{ $badge }}
                        </div>
                    @endif

                    @isset($headerActions)
                        {{ $headerActions }}
                    @endisset
                </div>
            @endif
        </div>
    @endif

    @if (! $slot->isEmpty())
        <div class="{{ filled($title) || filled($eyebrow) || filled($description) || filled($badge) || isset($headerActions) ? 'mt-5' : '' }}">
            {{ $slot }}
        </div>
    @endif
</section>
