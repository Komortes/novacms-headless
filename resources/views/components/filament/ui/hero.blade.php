@props([
    'eyebrow' => null,
    'title' => null,
    'description' => null,
    'tone' => 'slate',
])

@php
    $tones = [
        'slate' => [
            'section' => 'border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 shadow-[0_20px_48px_-32px_rgba(15,23,42,0.6)] dark:border-slate-800',
            'eyebrow' => 'border-white/15 bg-white/10 text-indigo-100',
            'title' => 'text-white',
            'description' => 'text-slate-200/90',
            'aside' => 'border-white/10 bg-white/8 text-slate-200',
            'glow' => 'bg-indigo-400/20',
        ],
        'sky' => [
            'section' => 'border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-sky-950 shadow-[0_20px_48px_-32px_rgba(8,47,73,0.65)] dark:border-slate-800',
            'eyebrow' => 'border-white/15 bg-white/10 text-sky-100',
            'title' => 'text-white',
            'description' => 'text-slate-200/90',
            'aside' => 'border-white/10 bg-white/8 text-slate-200',
            'glow' => 'bg-sky-400/20',
        ],
        'amber' => [
            'section' => 'border-amber-200 bg-gradient-to-br from-amber-50 via-orange-50 to-stone-50 shadow-[0_20px_48px_-32px_rgba(180,83,9,0.15)] dark:border-amber-800/40 dark:from-gray-900 dark:via-gray-900 dark:to-gray-950',
            'eyebrow' => 'border-amber-300/60 bg-white/70 text-amber-900 dark:border-amber-700/40 dark:bg-white/5 dark:text-amber-100',
            'title' => 'text-gray-950 dark:text-white',
            'description' => 'text-gray-700 dark:text-gray-300',
            'aside' => 'border-amber-200/70 bg-white/80 text-gray-700 dark:border-amber-800/30 dark:bg-white/5 dark:text-gray-200',
            'glow' => 'bg-amber-300/30',
        ],
    ];

    $toneClasses = $tones[$tone] ?? $tones['slate'];
@endphp

<section {{ $attributes->class(['relative isolate overflow-hidden rounded-[1.75rem] border shadow-sm', $toneClasses['section']]) }}>
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <div @class(['absolute -right-10 -top-10 h-36 w-36 rounded-full opacity-70 blur-2xl', $toneClasses['glow']])></div>
        <div class="absolute inset-0 bg-[linear-gradient(120deg,rgba(255,255,255,0.06),transparent_28%,transparent_72%,rgba(255,255,255,0.03))]"></div>
    </div>

    <div @class([
        'relative z-10 grid gap-5 px-5 py-5 lg:px-7 lg:py-6',
        'lg:grid-cols-[1.6fr_0.9fr]' => isset($aside),
    ])>
        <div>
            @if (filled($eyebrow))
                <div @class([
                    'inline-flex items-center gap-2 rounded-full border px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.2em]',
                    $toneClasses['eyebrow'],
                ])>
                    {{ $eyebrow }}
                </div>
            @endif

            @if (filled($title))
                <h2 @class(['mt-3 text-xl font-semibold tracking-tight sm:text-2xl', $toneClasses['title']])>{{ $title }}</h2>
            @endif

            @if (filled($description))
                <p @class(['mt-2 max-w-2xl text-sm leading-6', $toneClasses['description']])>{{ $description }}</p>
            @endif

            @if (! $slot->isEmpty())
                <div class="mt-4">
                    {{ $slot }}
                </div>
            @endif
        </div>

        @isset($aside)
            <aside @class(['rounded-[1.25rem] border p-4 backdrop-blur-sm', $toneClasses['aside']])>
                {{ $aside }}
            </aside>
        @endisset
    </div>
</section>
