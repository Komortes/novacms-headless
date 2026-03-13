<div class="space-y-6">
    <x-filament.ui.hero
        tone="slate"
        eyebrow="Control Surface"
        title="NovaCMS Admin"
        description="A role-aware workspace for content operations, prompt governance, and runtime oversight."
    >
        <x-slot:aside>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-300">Current Focus</p>
            <div class="mt-4 space-y-3 text-sm text-slate-200">
                <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                    <p class="font-semibold text-white">Signed in as</p>
                    <p class="mt-1 text-slate-300">{{ $roleLabel }}</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                    <p class="font-semibold text-white">Operational stance</p>
                    <p class="mt-1 text-slate-300">{{ $roleFocus }}</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                    <p class="font-semibold text-white">Runtime pressure</p>
                    <p class="mt-1 text-slate-300">
                        {{ $alertsCount > 0 ? $alertsCount . ' active alerts need attention.' : 'No active queue alerts right now.' }}
                    </p>
                </div>
            </div>
        </x-slot:aside>

        <div class="mt-5 flex flex-wrap gap-2">
            <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-slate-200 ring-1 ring-white/10">
                Content {{ $totalContent }}
            </span>
            <span class="inline-flex items-center rounded-full bg-emerald-400/15 px-3 py-1 text-xs font-medium text-emerald-100 ring-1 ring-emerald-300/20">
                Published {{ $publishedContent }}
            </span>
            <span class="inline-flex items-center rounded-full bg-sky-400/15 px-3 py-1 text-xs font-medium text-sky-100 ring-1 ring-sky-300/20">
                AI ready {{ $readySummaries }}
            </span>
            <span class="inline-flex items-center rounded-full bg-rose-400/15 px-3 py-1 text-xs font-medium text-rose-100 ring-1 ring-rose-300/20">
                AI failed {{ $failedSummaries }}
            </span>
        </div>
    </x-filament.ui.hero>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <x-filament.ui.metric-card
            label="Total Content"
            :value="$totalContent"
            description="All posts and pages in the workspace."
            tone="indigo"
        />
        <x-filament.ui.metric-card
            label="Drafts"
            :value="$draftContent"
            description="Editorial items still in progress."
            tone="amber"
        />
        <x-filament.ui.metric-card
            label="AI Ready"
            :value="$readySummaries"
            description="Summaries available for review and delivery."
            tone="emerald"
        />
        <x-filament.ui.metric-card
            label="Queue Pressure"
            :value="$pendingSummaries"
            description="Pending or generating AI runs."
            tone="sky"
        />
        <x-filament.ui.metric-card
            label="Active Prompts"
            :value="$activePrompts"
            description="Live prompt contracts currently driving output."
            tone="rose"
        />
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
        <x-filament.ui.panel
            eyebrow="Operator Notes"
            title="Where To Spend Time"
        >
            <div class="grid gap-3 md:grid-cols-2">
                <div class="rounded-2xl bg-gray-50 px-4 py-4 dark:bg-gray-800">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Editorial cadence</p>
                    <p class="mt-2 text-sm leading-6 text-gray-700 dark:text-gray-200">
                        {{ $draftContent > 0 ? $draftContent . ' drafts are still waiting for final review.' : 'No draft backlog right now.' }}
                    </p>
                </div>
                <div class="rounded-2xl bg-gray-50 px-4 py-4 dark:bg-gray-800">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">AI reliability</p>
                    <p class="mt-2 text-sm leading-6 text-gray-700 dark:text-gray-200">
                        {{ $failedSummaries > 0 ? $failedSummaries . ' summary runs need retry or provider review.' : 'No failed summary runs are waiting.' }}
                    </p>
                </div>
                <div class="rounded-2xl bg-gray-50 px-4 py-4 dark:bg-gray-800">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Runtime flow</p>
                    <p class="mt-2 text-sm leading-6 text-gray-700 dark:text-gray-200">
                        {{ $pendingSummaries > 0 ? 'Queue pressure is visible. Check Queue Center before launching additional runs.' : 'Queue is calm enough for normal editorial work.' }}
                    </p>
                </div>
                <div class="rounded-2xl bg-gray-50 px-4 py-4 dark:bg-gray-800">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Prompt contract</p>
                    <p class="mt-2 text-sm leading-6 text-gray-700 dark:text-gray-200">
                        {{ $activePrompts > 0 ? $activePrompts . ' active prompt families are shaping production output.' : 'Prompt registry is empty and needs a baseline version.' }}
                    </p>
                </div>
            </div>
        </x-filament.ui.panel>

        <x-filament.ui.panel
            tone="sky"
            eyebrow="Quick Links"
            title="Move Through The Panel"
        >
            <div class="grid gap-3">
                @foreach ($quickLinks as $link)
                    <a href="{{ $link['href'] }}" class="nova-link-tile">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $link['label'] }}</p>
                                <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $link['description'] }}</p>
                            </div>
                            <span @class([
                                'rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide',
                                'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-200' => $link['tone'] === 'indigo',
                                'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200' => $link['tone'] === 'amber',
                                'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-200' => $link['tone'] === 'rose',
                                'bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-200' => $link['tone'] === 'sky',
                                'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200' => $link['tone'] === 'emerald',
                            ])>
                                open
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </x-filament.ui.panel>
    </section>
</div>
