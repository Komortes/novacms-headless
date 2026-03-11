@props([
    'eyebrow' => null,
    'title' => null,
    'description' => null,
    'tone' => 'slate',
])

@php
    $tones = [
        'slate' => [
            'section' => 'border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 dark:border-slate-800',
            'eyebrow' => 'border-white/15 bg-white/10 text-indigo-100',
            'title' => 'text-white',
            'description' => 'text-slate-200/90',
            'aside' => 'border-white/10 bg-white/5 text-slate-200',
        ],
        'sky' => [
            'section' => 'border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-sky-950 dark:border-slate-800',
            'eyebrow' => 'border-white/15 bg-white/10 text-sky-100',
            'title' => 'text-white',
            'description' => 'text-slate-200/90',
            'aside' => 'border-white/10 bg-white/5 text-slate-200',
        ],
        'amber' => [
            'section' => 'border-amber-200 bg-gradient-to-br from-amber-50 via-orange-50 to-stone-50 dark:border-amber-800/40 dark:from-gray-900 dark:via-gray-900 dark:to-gray-950',
            'eyebrow' => 'border-amber-300/60 bg-white/70 text-amber-900 dark:border-amber-700/40 dark:bg-white/5 dark:text-amber-100',
            'title' => 'text-gray-950 dark:text-white',
            'description' => 'text-gray-700 dark:text-gray-300',
            'aside' => 'border-amber-200/70 bg-white/75 text-gray-700 dark:border-amber-800/30 dark:bg-white/5 dark:text-gray-200',
        ],
    ];

    $toneClasses = $tones[$tone] ?? $tones['slate'];
@endphp

<section {{ $attributes->class(['overflow-hidden rounded-3xl border shadow-sm', $toneClasses['section']]) }}>
    <div @class([
        'grid gap-6 px-6 py-6 lg:px-8',
        'lg:grid-cols-[1.6fr_0.9fr]' => isset($aside),
    ])>
        <div>
            @if (filled($eyebrow))
                <div @class([
                    'inline-flex items-center gap-2 rounded-full border px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em]',
                    $toneClasses['eyebrow'],
                ])>
                    {{ $eyebrow }}
                </div>
            @endif

            @if (filled($title))
                <h2 @class(['mt-4 text-2xl font-semibold tracking-tight', $toneClasses['title']])>{{ $title }}</h2>
            @endif

            @if (filled($description))
                <p @class(['mt-3 max-w-2xl text-sm leading-6', $toneClasses['description']])>{{ $description }}</p>
            @endif

            @if (! $slot->isEmpty())
                <div class="mt-5">
                    {{ $slot }}
                </div>
            @endif
        </div>

        @isset($aside)
            <aside @class(['rounded-2xl border p-5 backdrop-blur', $toneClasses['aside']])>
                {{ $aside }}
            </aside>
        @endisset
    </div>
</section>
