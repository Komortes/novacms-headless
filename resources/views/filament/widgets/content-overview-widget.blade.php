<div class="space-y-5">
    <x-filament.ui.hero
        tone="slate"
        eyebrow="Control Surface"
        title="NovaCMS Admin"
        description="A role-aware control surface for headless content operations, AI summarization, prompt governance, and runtime health."
    >
        <x-slot:aside>
            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500">Current Focus</p>
            <div class="mt-3 space-y-2 text-sm">
                <div class="rounded-lg border border-white/[0.07] bg-white/[0.04] px-3 py-2.5">
                    <p class="text-xs font-semibold text-slate-300">Signed in as</p>
                    <p class="mt-0.5 text-slate-400">{{ $roleLabel }}</p>
                </div>
                <div class="rounded-lg border border-white/[0.07] bg-white/[0.04] px-3 py-2.5">
                    <p class="text-xs font-semibold text-slate-300">Operational stance</p>
                    <p class="mt-0.5 text-slate-400">{{ $roleFocus }}</p>
                </div>
                <div class="rounded-lg border border-white/[0.07] bg-white/[0.04] px-3 py-2.5">
                    <p class="text-xs font-semibold text-slate-300">Runtime pressure</p>
                    <p class="mt-0.5 text-slate-400">
                        {{ $alertsCount > 0 ? $alertsCount . ' active alerts need attention.' : 'No active queue alerts right now.' }}
                    </p>
                </div>
            </div>
        </x-slot:aside>

        <div class="mt-4 flex flex-wrap gap-1.5">
            <span class="inline-flex items-center rounded border border-white/10 bg-white/[0.07] px-2.5 py-0.5 text-[11px] font-medium text-slate-300">
                Content {{ $totalContent }}
            </span>
            <span class="inline-flex items-center rounded border border-emerald-700/40 bg-emerald-900/25 px-2.5 py-0.5 text-[11px] font-medium text-emerald-300">
                Published {{ $publishedContent }}
            </span>
            <span class="inline-flex items-center rounded border border-sky-700/40 bg-sky-900/25 px-2.5 py-0.5 text-[11px] font-medium text-sky-300">
                Review-ready {{ $reviewReadyCount }}
            </span>
            <span class="inline-flex items-center rounded border border-rose-700/40 bg-rose-900/25 px-2.5 py-0.5 text-[11px] font-medium text-rose-300">
                AI failed {{ $failedSummaries }}
            </span>
        </div>
    </x-filament.ui.hero>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <x-filament.ui.metric-card label="Total Content"   :value="$totalContent"      description="All posts and pages in the workspace."                    tone="indigo" />
        <x-filament.ui.metric-card label="Drafts"          :value="$draftContent"      description="Editorial items still in progress."                       tone="amber" />
        <x-filament.ui.metric-card label="Review Ready"    :value="$reviewReadyCount"  description="Drafts with usable AI output ready for editorial review."  tone="emerald" />
        <x-filament.ui.metric-card label="Queue Pressure"  :value="$pendingSummaries"  description="Pending or generating AI runs."                           tone="sky" />
        <x-filament.ui.metric-card label="Active Prompts"  :value="$activePrompts"     description="Live prompt contracts shaping generation."                tone="rose" />
    </section>

    <section class="grid gap-5 xl:grid-cols-[1.05fr_0.95fr]">
        <x-filament.ui.panel
            eyebrow="Start Here"
            title="Guided Product Walkthrough"
            :description="$demoReport['scenario_ok']
                ? 'Use these steps to tell the product story from seeded content to headless delivery without improvising the flow.'
                : 'The seeded walkthrough drifted. Restore the demo before using this dashboard as your tour script.'"
            :badge="$demoReport['scenario_ok'] ? 'seeded' : 'needs reset'"
            :tone="$demoReport['scenario_ok'] ? 'default' : 'amber'"
        >
            <div class="space-y-2">
                @foreach ($demoWalkthrough as $step)
                    <a href="{{ $step['href'] }}" class="nova-link-tile">
                        <div class="flex items-start gap-3">
                            <span @class([
                                'mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg font-mono text-xs font-semibold',
                                'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' => $step['tone'] === 'indigo',
                                'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'   => $step['tone'] === 'amber',
                                'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'       => $step['tone'] === 'rose',
                                'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300'           => $step['tone'] === 'sky',
                                'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' => $step['tone'] === 'emerald',
                            ])>
                                {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-slate-950 dark:text-slate-100">{{ $step['title'] }}</p>
                                <p class="mt-0.5 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $step['description'] }}</p>
                            </div>
                            <span class="shrink-0 rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">open</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </x-filament.ui.panel>

        <x-filament.ui.panel
            :tone="$demoReport['scenario_ok'] ? 'emerald' : 'amber'"
            eyebrow="Demo Ops"
            title="Reset And Validate The Demo"
            :description="$demoReport['scenario_ok']
                ? 'These commands keep the seeded walkthrough intact and prove the environment is healthy before someone touches the admin.'
                : 'The seeded walkthrough no longer matches the product story. Use reset before the next demo.'"
        >
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($demoSignals as $signal)
                    <div @class([
                        'nova-mini-stat',
                        'border-emerald-200 dark:border-emerald-900/40' => $signal['tone'] === 'emerald',
                        'border-rose-200 dark:border-rose-900/40'       => $signal['tone'] === 'rose',
                        'border-amber-200 dark:border-amber-900/40'     => $signal['tone'] === 'amber',
                        'border-sky-200 dark:border-sky-900/40'         => $signal['tone'] === 'sky',
                        'border-indigo-200 dark:border-indigo-900/40'   => $signal['tone'] === 'indigo',
                    ])>
                        <p class="nova-mini-stat-label">{{ $signal['label'] }}</p>
                        <p @class([
                            'nova-mini-stat-value text-base',
                            'text-emerald-600 dark:text-emerald-400' => $signal['tone'] === 'emerald',
                            'text-rose-600 dark:text-rose-400'       => $signal['tone'] === 'rose',
                            'text-amber-600 dark:text-amber-400'     => $signal['tone'] === 'amber',
                            'text-sky-600 dark:text-sky-400'         => $signal['tone'] === 'sky',
                        ])>{{ $signal['value'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-400">
                {{ $demoReport['scenario_ok']
                    ? 'Seeded accounts, content states, and prompt baseline are present. Use demo-check to verify runtime before a live walkthrough, then demo-models when you want fresh generation.'
                    : 'Seeded accounts or reference records are missing. Run demo-reset to restore the product walkthrough to a known-good state.' }}
            </div>

            <div class="mt-4 space-y-2">
                @foreach ($demoOperations as $operation)
                    <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800">
                        <div class="border-b border-slate-200 bg-slate-950 px-4 py-2.5 dark:border-slate-800">
                            <code class="font-mono text-sm text-slate-100">{{ $operation['command'] }}</code>
                        </div>
                        <div class="px-4 py-3">
                            <p class="text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $operation['description'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament.ui.panel>
    </section>

    <section class="grid gap-5 xl:grid-cols-[0.8fr_1.2fr]">
        <x-filament.ui.panel
            eyebrow="Operating Signals"
            title="Read The System Before Acting"
            description="These three signals compress content operations, runtime pressure, and AI governance into the quickest possible scan."
        >
            <div class="grid gap-2 md:grid-cols-3 xl:grid-cols-1">
                @foreach ($operatingSignals as $signal)
                    <a href="{{ $signal['href'] }}" class="nova-link-tile">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-950 dark:text-slate-100">{{ $signal['title'] }}</p>
                                <p class="mt-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $signal['value'] }}</p>
                                <p class="mt-1.5 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $signal['description'] }}</p>
                            </div>
                            <span @class([
                                'shrink-0 rounded-md px-2 py-0.5 text-[11px] font-semibold',
                                'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' => $signal['tone'] === 'indigo',
                                'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'   => $signal['tone'] === 'amber',
                                'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'       => $signal['tone'] === 'rose',
                                'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300'           => $signal['tone'] === 'sky',
                                'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' => $signal['tone'] === 'emerald',
                            ])>view</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </x-filament.ui.panel>

        <x-filament.ui.panel
            tone="sky"
            eyebrow="Command Deck"
            title="Move Through The Panel"
            description="Use these routes once the dashboard tells you whether the next constraint sits in content, delivery, or runtime."
        >
            <div class="grid gap-2 md:grid-cols-2">
                @foreach ($quickLinks as $link)
                    <a href="{{ $link['href'] }}" class="nova-link-tile">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-950 dark:text-slate-100">{{ $link['label'] }}</p>
                                <p class="mt-0.5 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $link['description'] }}</p>
                            </div>
                            <span @class([
                                'shrink-0 rounded-md px-2 py-0.5 text-[11px] font-semibold',
                                'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' => $link['tone'] === 'indigo',
                                'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'   => $link['tone'] === 'amber',
                                'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'       => $link['tone'] === 'rose',
                                'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300'           => $link['tone'] === 'sky',
                                'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' => $link['tone'] === 'emerald',
                            ])>open</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </x-filament.ui.panel>
    </section>

    <section class="grid gap-5 xl:grid-cols-3">
        @foreach ($workflowLanes as $lane)
            <x-filament.ui.panel
                :tone="$lane['tone']"
                :eyebrow="$lane['eyebrow']"
                :title="$lane['title']"
                :description="$lane['description']"
            >
                <div class="grid gap-2 sm:grid-cols-3 xl:grid-cols-1">
                    @foreach ($lane['stats'] as $stat)
                        <div class="nova-mini-stat">
                            <p class="nova-mini-stat-label">{{ $stat['label'] }}</p>
                            <p class="nova-mini-stat-value">{{ $stat['value'] }}</p>
                        </div>
                    @endforeach
                </div>

                <a href="{{ $lane['href'] }}" class="mt-4 block nova-link-tile">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-950 dark:text-slate-100">{{ $lane['cta'] }}</p>
                            <p class="mt-0.5 text-sm leading-6 text-slate-500 dark:text-slate-400">Use this lane when the dashboard tells you where the next bottleneck sits.</p>
                        </div>
                        <span @class([
                            'shrink-0 rounded-md px-2 py-0.5 text-[11px] font-semibold',
                            'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' => $lane['tone'] === 'indigo',
                            'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'   => $lane['tone'] === 'amber',
                            'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300'           => $lane['tone'] === 'sky',
                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' => $lane['tone'] === 'emerald',
                        ])>open</span>
                    </div>
                </a>
            </x-filament.ui.panel>
        @endforeach
    </section>

    <section class="grid gap-5 xl:grid-cols-[1.1fr_0.9fr]">
        <x-filament.ui.panel
            eyebrow="Needs Attention"
            title="What To Triage Next"
            description="Use this as the first stop before diving into content, AI summarization, or runtime details."
        >
            <div class="grid gap-2 md:grid-cols-2">
                @foreach ($attentionItems as $item)
                    <a href="{{ $item['href'] }}" class="nova-link-tile">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-950 dark:text-slate-100">{{ $item['title'] }}</p>
                                <p class="mt-0.5 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $item['description'] }}</p>
                            </div>
                            <span @class([
                                'shrink-0 rounded-md px-2 py-0.5 text-[11px] font-semibold',
                                'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' => $item['tone'] === 'indigo',
                                'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'   => $item['tone'] === 'amber',
                                'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'       => $item['tone'] === 'rose',
                                'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300'           => $item['tone'] === 'sky',
                                'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' => $item['tone'] === 'emerald',
                            ])>act</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </x-filament.ui.panel>

        <x-filament.ui.panel
            tone="{{ $alertsCount > 0 ? 'rose' : 'emerald' }}"
            eyebrow="Runtime Alerts"
            title="Current Operational Risk"
            :badge="$alertsCount > 0 ? $alertsCount . ' active' : 'stable'"
        >
            <div class="space-y-2">
                @forelse ($alerts as $alert)
                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-800/40">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-slate-950 dark:text-slate-100">{{ $alert['title'] }}</p>
                            <span class="rounded-md bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-700 dark:bg-rose-900/30 dark:text-rose-300">
                                {{ $alert['severity'] }}
                            </span>
                        </div>
                        <p class="mt-1.5 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $alert['message'] }}</p>
                    </div>
                @empty
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm text-emerald-800 dark:border-emerald-900/30 dark:bg-emerald-900/10 dark:text-emerald-300">
                        No queue-health alerts are active right now.
                    </div>
                @endforelse
            </div>
        </x-filament.ui.panel>
    </section>

    <section class="grid gap-5 xl:grid-cols-[1.1fr_0.9fr]">
        <x-filament.ui.panel
            eyebrow="Recent Content"
            title="Latest Workspace Activity"
            description="Recent records that changed and may need review, AI refresh, or headless delivery attention."
        >
            <div class="space-y-2">
                @forelse ($recentContent as $item)
                    <a href="{{ $item['href'] }}" class="nova-link-tile">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-950 dark:text-slate-100">{{ $item['title'] }}</p>
                                <p class="mt-0.5 font-mono text-[11px] uppercase tracking-[0.12em] text-slate-400 dark:text-slate-500">
                                    {{ $item['slug'] }} · {{ $item['updated_at'] }}
                                </p>
                            </div>
                            <div class="flex shrink-0 flex-wrap justify-end gap-1.5">
                                <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                    {{ ucfirst($item['status']) }}
                                </span>
                                <span @class([
                                    'rounded-md px-2 py-0.5 text-[11px] font-semibold',
                                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' => $item['summary_status'] === 'ready',
                                    'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300'               => $item['summary_status'] === 'generating',
                                    'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'       => $item['summary_status'] === 'pending',
                                    'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'           => $item['summary_status'] === 'failed',
                                    'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'          => $item['summary_status'] === 'missing',
                                ])>
                                    AI {{ $item['summary_status'] }}
                                </span>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="nova-empty-state">No recent content activity yet.</div>
                @endforelse
            </div>
        </x-filament.ui.panel>

        <x-filament.ui.panel
            tone="sky"
            eyebrow="Recent AI Events"
            title="Generation Feed"
            description="Latest AI summarization events across the workspace."
        >
            <div class="space-y-2">
                @forelse ($recentEvents as $event)
                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-800/40">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-950 dark:text-slate-100">{{ $event['title'] }}</p>
                                <p class="mt-0.5 font-mono text-[11px] uppercase tracking-[0.12em] text-slate-400 dark:text-slate-500">
                                    {{ \Illuminate\Support\Str::headline($event['event']) }} · {{ $event['provider'] }} · {{ $event['updated_at'] }}
                                </p>
                            </div>
                            @if ($event['href'])
                                <a href="{{ $event['href'] }}" class="shrink-0 rounded-md bg-sky-50 px-2 py-0.5 text-[11px] font-semibold text-sky-700 dark:bg-sky-900/30 dark:text-sky-300">
                                    open
                                </a>
                            @endif
                        </div>
                        <p class="mt-1.5 text-sm leading-6 text-slate-500 dark:text-slate-400">
                            {{ $event['message'] !== '' ? $event['message'] : 'No additional event details captured.' }}
                        </p>
                    </div>
                @empty
                    <div class="nova-empty-state">No recent AI events yet.</div>
                @endforelse
            </div>
        </x-filament.ui.panel>
    </section>
</div>
