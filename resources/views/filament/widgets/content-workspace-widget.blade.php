<div class="space-y-4">
    <x-filament.ui.hero
        tone="slate"
        eyebrow="Content Workspace"
        title="Headless Content Operations"
        description="Use tabs and filters to narrow the content set, then open a record only when you need deeper review, AI regeneration, or delivery decisions."
    >
        <x-slot:aside>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-300">Fast Path</p>
            <ol class="mt-4 space-y-3 text-sm text-slate-200">
                <li class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                    <span class="font-semibold text-white">1. Filter the working set</span>
                    <p class="mt-1 text-slate-300">Use tabs for draft, AI pending, or failed runs before touching row actions.</p>
                </li>
                <li class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                    <span class="font-semibold text-white">2. Read AI status first</span>
                    <p class="mt-1 text-slate-300">The list is optimized to show publishing and AI state together.</p>
                </li>
                <li class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                    <span class="font-semibold text-white">3. Open the record for final review</span>
                    <p class="mt-1 text-slate-300">Use the detailed view only when you need TL;DR, FAQ, timeline, or source markdown.</p>
                </li>
            </ol>
        </x-slot:aside>

        <div class="mt-5 flex flex-wrap gap-2">
            <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-slate-200 ring-1 ring-white/10">
                Total {{ $totalContent }}
            </span>
            <span class="inline-flex items-center rounded-full bg-amber-400/15 px-3 py-1 text-xs font-medium text-amber-100 ring-1 ring-amber-300/20">
                Draft {{ $draftCount }}
            </span>
            <span class="inline-flex items-center rounded-full bg-sky-400/15 px-3 py-1 text-xs font-medium text-sky-100 ring-1 ring-sky-300/20">
                AI in queue {{ $pendingAiCount }}
            </span>
            <span class="inline-flex items-center rounded-full bg-rose-400/15 px-3 py-1 text-xs font-medium text-rose-100 ring-1 ring-rose-300/20">
                AI failed {{ $failedAiCount }}
            </span>
        </div>
    </x-filament.ui.hero>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-filament.ui.metric-card
            label="Total Records"
            :value="$totalContent"
            description="All posts and pages available in the workspace."
            tone="indigo"
        />
        <x-filament.ui.metric-card
            label="Drafts"
            :value="$draftCount"
            description="Editorial items still in preparation."
            tone="amber"
        />
        <x-filament.ui.metric-card
            label="Published"
            :value="$publishedCount"
            description="Content already available to headless consumers."
            tone="emerald"
        />
        <x-filament.ui.metric-card
            label="AI Attention"
            :value="$pendingAiCount + $generatingAiCount + $failedAiCount"
            description="Queued, generating, or failed runs that still need operator attention."
            tone="rose"
        />
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.25fr_0.95fr]">
        <x-filament.ui.panel
            tone="indigo"
            eyebrow="Fast Filters"
            title="Open The Right Working Set"
            description="These routes map directly to list tabs, so you can start in the correct content or AI lane instead of searching the whole table."
        >
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($filterRoutes as $route)
                    <a href="{{ $route['href'] }}" class="nova-link-tile">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-950 dark:text-slate-100">{{ $route['label'] }}</p>
                                <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $route['description'] }}</p>
                            </div>
                            <span @class([
                                'rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide',
                                'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-200' => $route['tone'] === 'indigo',
                                'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200' => $route['tone'] === 'amber',
                                'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-200' => $route['tone'] === 'rose',
                                'bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-200' => $route['tone'] === 'sky',
                                'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200' => $route['tone'] === 'emerald',
                            ])>
                                {{ $route['badge'] }}
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </x-filament.ui.panel>

        <x-filament.ui.panel
            eyebrow="Working Rules"
            title="How To Use The List Well"
        >
            <div class="space-y-3">
                <div class="nova-signal-card">
                    <p class="nova-signal-card-title">Start from tabs, not global search</p>
                    <p class="nova-signal-card-copy">`Draft`, `Pending`, `Generating`, `Ready`, and `Failed` are operational lanes, not just filters.</p>
                </div>
                <div class="nova-signal-card">
                    <p class="nova-signal-card-title">Use row color before opening the record</p>
                    <p class="nova-signal-card-copy">The list now signals failure, queue activity, and review-ready drafts directly in the table.</p>
                </div>
                <div class="nova-signal-card">
                    <p class="nova-signal-card-title">Reserve bulk actions for clear batch work</p>
                    <p class="nova-signal-card-copy">Mass-generate AI only when the provider baseline and prompt contract are already understood.</p>
                </div>
            </div>

            @if ($workspaceRoutes !== [])
                <div class="mt-4 space-y-3">
                    @foreach ($workspaceRoutes as $route)
                        <a href="{{ $route['href'] }}" class="nova-link-tile">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-950 dark:text-slate-100">{{ $route['label'] }}</p>
                                    <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $route['description'] }}</p>
                                </div>
                                <span @class([
                                    'rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide',
                                    'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-200' => $route['tone'] === 'indigo',
                                    'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200' => $route['tone'] === 'amber',
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200' => $route['tone'] === 'emerald',
                                ])>
                                    open
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </x-filament.ui.panel>
    </section>
</div>
