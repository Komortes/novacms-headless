<div class="space-y-4">
    <x-filament.ui.hero
        tone="slate"
        eyebrow="Content Workspace"
        title="Headless Content Operations"
        description="Use tabs and filters to narrow the content set, then open a record only when you need deeper review, AI regeneration, or delivery decisions."
    >
        <x-slot:aside>
            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500">Fast Path</p>
            <ol class="mt-3 space-y-2 text-sm">
                <li class="rounded-lg border border-white/[0.07] bg-white/[0.04] px-3 py-2.5">
                    <span class="text-xs font-semibold text-slate-200">1. Filter the working set</span>
                    <p class="mt-0.5 text-slate-500">Use tabs for draft, AI pending, or failed runs before touching row actions.</p>
                </li>
                <li class="rounded-lg border border-white/[0.07] bg-white/[0.04] px-3 py-2.5">
                    <span class="text-xs font-semibold text-slate-200">2. Read AI status first</span>
                    <p class="mt-0.5 text-slate-500">The list is optimized to show publishing and AI state together.</p>
                </li>
                <li class="rounded-lg border border-white/[0.07] bg-white/[0.04] px-3 py-2.5">
                    <span class="text-xs font-semibold text-slate-200">3. Open the record for final review</span>
                    <p class="mt-0.5 text-slate-500">Use the detailed view only when you need TL;DR, FAQ, timeline, or source markdown.</p>
                </li>
            </ol>
        </x-slot:aside>

        <div class="mt-4 flex flex-wrap gap-1.5">
            <span class="inline-flex items-center rounded border border-white/10 bg-white/[0.07] px-2.5 py-0.5 text-[11px] font-medium text-slate-300">
                Total {{ $totalContent }}
            </span>
            <span class="inline-flex items-center rounded border border-amber-700/40 bg-amber-900/25 px-2.5 py-0.5 text-[11px] font-medium text-amber-300">
                Draft {{ $draftCount }}
            </span>
            <span class="inline-flex items-center rounded border border-sky-700/40 bg-sky-900/25 px-2.5 py-0.5 text-[11px] font-medium text-sky-300">
                AI in queue {{ $pendingAiCount }}
            </span>
            <span class="inline-flex items-center rounded border border-rose-700/40 bg-rose-900/25 px-2.5 py-0.5 text-[11px] font-medium text-rose-300">
                AI failed {{ $failedAiCount }}
            </span>
        </div>
    </x-filament.ui.hero>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-filament.ui.metric-card label="Total Records" :value="$totalContent"   description="All posts and pages available in the workspace."           tone="indigo" />
        <x-filament.ui.metric-card label="Drafts"        :value="$draftCount"     description="Editorial items still in preparation."                      tone="amber" />
        <x-filament.ui.metric-card label="Published"     :value="$publishedCount" description="Content already available to headless consumers."           tone="emerald" />
        <x-filament.ui.metric-card label="AI Attention"  :value="$pendingAiCount + $generatingAiCount + $failedAiCount" description="Queued, generating, or failed runs that still need operator attention." tone="rose" />
    </section>

    <section class="grid gap-5 xl:grid-cols-[1.25fr_0.95fr]">
        <x-filament.ui.panel
            tone="indigo"
            eyebrow="Fast Filters"
            title="Open The Right Working Set"
            description="These routes map directly to list tabs, so you can start in the correct content or AI lane instead of searching the whole table."
        >
            <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($filterRoutes as $route)
                    <a href="{{ $route['href'] }}" class="nova-link-tile">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-950 dark:text-slate-100">{{ $route['label'] }}</p>
                                <p class="mt-0.5 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $route['description'] }}</p>
                            </div>
                            <span @class([
                                'shrink-0 rounded-md px-2 py-0.5 text-[11px] font-semibold',
                                'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' => $route['tone'] === 'indigo',
                                'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'   => $route['tone'] === 'amber',
                                'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'       => $route['tone'] === 'rose',
                                'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300'           => $route['tone'] === 'sky',
                                'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' => $route['tone'] === 'emerald',
                            ])>{{ $route['badge'] }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </x-filament.ui.panel>

        <x-filament.ui.panel
            eyebrow="Working Rules"
            title="How To Use The List Well"
        >
            <div class="space-y-2">
                <div class="nova-signal-card">
                    <p class="nova-signal-card-title">Start from tabs, not global search</p>
                    <p class="nova-signal-card-copy"><code class="nova-inline-code">Draft</code>, <code class="nova-inline-code">Pending</code>, <code class="nova-inline-code">Generating</code>, <code class="nova-inline-code">Ready</code>, and <code class="nova-inline-code">Failed</code> are operational lanes, not just filters.</p>
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
                <div class="mt-4 space-y-2">
                    @foreach ($workspaceRoutes as $route)
                        <a href="{{ $route['href'] }}" class="nova-link-tile">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-950 dark:text-slate-100">{{ $route['label'] }}</p>
                                    <p class="mt-0.5 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $route['description'] }}</p>
                                </div>
                                <span @class([
                                    'shrink-0 rounded-md px-2 py-0.5 text-[11px] font-semibold',
                                    'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' => $route['tone'] === 'indigo',
                                    'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'   => $route['tone'] === 'amber',
                                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' => $route['tone'] === 'emerald',
                                ])>open</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </x-filament.ui.panel>
    </section>
</div>
