@props([
    'eyebrow'     => null,
    'title'       => null,
    'description' => null,
    'tone'        => 'slate',
])

@php
$tones = [
    'slate' => [
        'section'     => 'bg-[#0a0f1e] border-white/[0.07]',
        'eyebrow'     => 'border-white/10 bg-white/[0.07] text-slate-300',
        'title'       => 'text-white',
        'description' => 'text-slate-400',
        'aside'       => 'border-white/[0.08] bg-white/[0.04]',
        'grid'        => 'rgba(255,255,255,0.025)',
    ],
    'sky' => [
        'section'     => 'bg-[#060d1f] border-sky-900/40',
        'eyebrow'     => 'border-sky-800/50 bg-sky-950/60 text-sky-300',
        'title'       => 'text-white',
        'description' => 'text-slate-400',
        'aside'       => 'border-sky-900/30 bg-sky-950/40',
        'grid'        => 'rgba(56,189,248,0.04)',
    ],
    'amber' => [
        'section'     => 'bg-[#0f0d00] border-amber-900/30',
        'eyebrow'     => 'border-amber-800/50 bg-amber-950/50 text-amber-300',
        'title'       => 'text-white',
        'description' => 'text-amber-200/60',
        'aside'       => 'border-amber-900/30 bg-amber-950/30',
        'grid'        => 'rgba(251,191,36,0.04)',
    ],
];

$tc = $tones[$tone] ?? $tones['slate'];
@endphp

<section {{ $attributes->class(['relative overflow-hidden rounded-2xl border', $tc['section']]) }}>
    {{-- Subtle grid texture --}}
    <div
        class="pointer-events-none absolute inset-0"
        style="background-image: linear-gradient({{ $tc['grid'] }} 1px, transparent 1px), linear-gradient(90deg, {{ $tc['grid'] }} 1px, transparent 1px); background-size: 48px 48px;"
    ></div>
    {{-- Faint edge vignette --}}
    <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-white/[0.03] via-transparent to-black/20"></div>

    <div @class([
        'relative z-10 grid gap-6 px-6 py-6 lg:px-8 lg:py-7',
        'lg:grid-cols-[1.55fr_1fr]' => isset($aside),
    ])>
        <div>
            @if (filled($eyebrow))
                <div @class([
                    'inline-flex items-center rounded border px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.22em]',
                    $tc['eyebrow'],
                ])>
                    {{ $eyebrow }}
                </div>
            @endif

            @if (filled($title))
                <h2 @class(['mt-3 text-xl font-semibold tracking-tight sm:text-2xl', $tc['title']])>
                    {{ $title }}
                </h2>
            @endif

            @if (filled($description))
                <p @class(['mt-2 max-w-2xl text-sm leading-6', $tc['description']])>
                    {{ $description }}
                </p>
            @endif

            @if (! $slot->isEmpty())
                <div class="mt-4">{{ $slot }}</div>
            @endif
        </div>

        @isset($aside)
            <aside @class(['rounded-xl border px-4 py-4 text-sm text-slate-300', $tc['aside']])>
                {{ $aside }}
            </aside>
        @endisset
    </div>
</section>
