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
        'default' => 'border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900',
        'rose' => 'border-rose-200 bg-white dark:border-rose-800/40 dark:bg-gray-900',
        'amber' => 'border-amber-200 bg-white dark:border-amber-800/40 dark:bg-gray-900',
        'sky' => 'border-sky-200 bg-white dark:border-sky-800/40 dark:bg-gray-900',
        'emerald' => 'border-emerald-200 bg-white dark:border-emerald-800/40 dark:bg-gray-900',
    ];
@endphp

<section {{ $attributes->class(['rounded-2xl border shadow-sm', $tones[$tone] ?? $tones['default'], $padding]) }}>
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
                        <div class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">
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
