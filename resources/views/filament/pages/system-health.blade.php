<x-filament-panels::page>
    @php
        $okCount = collect($checks)->where('status', 'ok')->count();
        $warnCount = collect($checks)->where('status', 'warn')->count();
        $failCount = collect($checks)->where('status', 'fail')->count();
    @endphp

    <div class="space-y-6">
        <x-filament.ui.hero
            tone="sky"
            eyebrow="Runtime Control Surface"
            title="System Health"
            description="Infrastructure checks for the headless CMS runtime: database, queue, websockets, and local AI services. Use this page to separate editor-side problems from environment-level failures before retrying jobs."
        >
            <x-slot:aside>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-300">Operator Guide</p>
                <div class="mt-4 space-y-3 text-sm text-slate-200">
                    <div class="rounded-2xl border border-emerald-400/15 bg-emerald-400/10 px-4 py-3">
                        <p class="font-semibold text-emerald-100">Healthy</p>
                        <p class="mt-1 text-emerald-50/80">Queue and runtime are ready for normal generation and retries.</p>
                    </div>
                    <div class="rounded-2xl border border-amber-300/15 bg-amber-300/10 px-4 py-3">
                        <p class="font-semibold text-amber-100">Warning</p>
                        <p class="mt-1 text-amber-50/80">Something is degraded. Jobs may still run, but risk is increasing.</p>
                    </div>
                    <div class="rounded-2xl border border-rose-300/15 bg-rose-300/10 px-4 py-3">
                        <p class="font-semibold text-rose-100">Failure</p>
                        <p class="mt-1 text-rose-50/80">Resolve infrastructure first, then return to Queue Center for retries.</p>
                    </div>
                </div>
            </x-slot:aside>

            <div class="mt-5 flex flex-wrap gap-2">
                <span @class([
                    'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1',
                    'bg-emerald-400/15 text-emerald-100 ring-emerald-300/20' => $ok,
                    'bg-rose-400/15 text-rose-100 ring-rose-300/20' => ! $ok,
                ])>
                    {{ $ok ? 'All required checks passed' : 'Runtime attention required' }}
                </span>
                <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-slate-200 ring-1 ring-white/10">
                    Last check {{ \Illuminate\Support\Carbon::parse($generated_at)->diffForHumans() }}
                </span>
            </div>
        </x-filament.ui.hero>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-filament.ui.metric-card
                label="Healthy Checks"
                :value="$okCount"
                description="Components responding as expected."
                tone="emerald"
            />
            <x-filament.ui.metric-card
                label="Warnings"
                :value="$warnCount"
                description="Degraded checks that should be monitored."
                tone="amber"
            />
            <x-filament.ui.metric-card
                label="Failures"
                :value="$failCount"
                description="Blocking failures before retries."
                tone="rose"
            />
            <x-filament.ui.metric-card
                label="Alerts"
                :value="count($alerts)"
                description="Operational thresholds from queue lag and failure growth."
                tone="sky"
            />
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.55fr_0.95fr]">
            <x-filament.ui.panel
                :tone="$failCount > 0 ? 'rose' : ($warnCount > 0 ? 'amber' : 'emerald')"
                eyebrow="Checks"
                title="Runtime Status Board"
                :badge="$ok ? 'healthy baseline' : 'needs action'"
            >
                <div class="rounded-[1.35rem] border border-white/70 bg-white/65 px-5 py-4 dark:border-white/10 dark:bg-slate-950/45">
                    <p class="text-sm font-semibold text-slate-950 dark:text-slate-100">
                        {{ $ok ? 'All required services are reachable. Queue-side retries are safe if content still needs reprocessing.' : 'At least one service is degraded or failing. Fix runtime conditions before treating editor-side symptoms.' }}
                    </p>
                    <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                        Last health snapshot was generated {{ \Illuminate\Support\Carbon::parse($generated_at)->diffForHumans() }}. Use the status board below to separate blocking failures from recoverable warnings.
                    </p>
                </div>

                <div class="mt-4 grid gap-4 xl:grid-cols-3">
                    @foreach ($statusBuckets as $bucket)
                        <section @class([
                            'rounded-[1.35rem] border p-4',
                            'border-rose-200 bg-rose-50/60 dark:border-rose-800/40 dark:bg-rose-900/10' => $bucket['tone'] === 'rose',
                            'border-amber-200 bg-amber-50/60 dark:border-amber-800/40 dark:bg-amber-900/10' => $bucket['tone'] === 'amber',
                            'border-emerald-200 bg-emerald-50/60 dark:border-emerald-800/40 dark:bg-emerald-900/10' => $bucket['tone'] === 'emerald',
                        ])>
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-950 dark:text-slate-100">{{ $bucket['label'] }}</p>
                                    <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $bucket['description'] }}</p>
                                </div>
                                <span @class([
                                    'rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide',
                                    'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200' => $bucket['tone'] === 'rose',
                                    'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200' => $bucket['tone'] === 'amber',
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200' => $bucket['tone'] === 'emerald',
                                ])>
                                    {{ count($bucket['items']) }}
                                </span>
                            </div>

                            <div class="mt-4 space-y-3">
                                @forelse ($bucket['items'] as $check)
                                    <article class="nova-signal-card bg-white/90 dark:bg-slate-950/75">
                                        <div class="flex items-start justify-between gap-3">
                                            <p class="nova-signal-card-title">{{ $check['component'] }}</p>
                                            <span @class([
                                                'shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide',
                                                'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200' => $check['status'] === 'ok',
                                                'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200' => $check['status'] === 'warn',
                                                'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200' => $check['status'] === 'fail',
                                            ])>
                                                {{ $check['status'] }}
                                            </span>
                                        </div>
                                        <p class="nova-signal-card-copy">{{ $check['message'] }}</p>

                                        @if (($check['meta'] ?? []) !== [])
                                            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                                                @foreach (($check['meta'] ?? []) as $key => $value)
                                                    <div class="nova-mini-stat bg-white/90 dark:bg-gray-950/70">
                                                        <p class="nova-mini-stat-label">{{ str_replace('_', ' ', (string) $key) }}</p>
                                                        <p class="nova-mini-stat-value break-words text-base">
                                                            @if (is_scalar($value) || $value === null)
                                                                {{ $value === null ? 'n/a' : (string) $value }}
                                                            @else
                                                                {{ json_encode($value, JSON_UNESCAPED_SLASHES) }}
                                                            @endif
                                                        </p>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </article>
                                @empty
                                    <div class="nova-empty-state">{{ $bucket['empty'] }}</div>
                                @endforelse
                            </div>
                        </section>
                    @endforeach
                </div>
            </x-filament.ui.panel>

            <div class="space-y-6">
                <x-filament.ui.panel
                    eyebrow="Priority Actions"
                    title="What To Do With This Snapshot"
                >
                    <div class="space-y-3">
                        @foreach ($priorityActions as $action)
                            <div @class([
                                'nova-signal-card',
                                'border-rose-200 bg-rose-50/70 dark:border-rose-800/40 dark:bg-rose-900/10' => $action['tone'] === 'rose',
                                'border-amber-200 bg-amber-50/70 dark:border-amber-800/40 dark:bg-amber-900/10' => $action['tone'] === 'amber',
                                'border-sky-200 bg-sky-50/70 dark:border-sky-800/40 dark:bg-sky-900/10' => $action['tone'] === 'sky',
                                'border-emerald-200 bg-emerald-50/70 dark:border-emerald-800/40 dark:bg-emerald-900/10' => $action['tone'] === 'emerald',
                            ])>
                                <p class="nova-signal-card-title">{{ $action['title'] }}</p>
                                <p class="nova-signal-card-copy">{{ $action['description'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </x-filament.ui.panel>

                <x-filament.ui.panel
                    eyebrow="Operating Routes"
                    title="Move Back Into The Workflow"
                >
                    <div class="space-y-3">
                        @foreach ($operatingLinks as $link)
                            <a href="{{ $link['href'] }}" class="nova-link-tile">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-950 dark:text-slate-100">{{ $link['label'] }}</p>
                                        <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $link['description'] }}</p>
                                    </div>
                                    <span @class([
                                        'rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide',
                                        'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-200' => $link['tone'] === 'indigo',
                                        'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200' => $link['tone'] === 'amber',
                                        'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200' => $link['tone'] === 'emerald',
                                    ])>
                                        open
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </x-filament.ui.panel>
            </div>
        </section>

        @if (count($alerts) > 0)
            <x-filament.ui.panel
                tone="rose"
                eyebrow="Operational Alerts"
                title="Queue Risk Summary"
                :badge="count($alerts) . ' active'"
            >
                <div class="grid gap-3 lg:grid-cols-2">
                    @foreach ($alerts as $alert)
                        <article class="rounded-2xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-800/40 dark:bg-rose-900/15">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-rose-950 dark:text-rose-100">{{ $alert['title'] }}</p>
                                    <p class="mt-2 text-sm leading-6 text-rose-800 dark:text-rose-200">{{ $alert['message'] }}</p>
                                </div>
                                <span class="rounded-full bg-white/80 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-rose-700 dark:bg-rose-950/40 dark:text-rose-200">
                                    {{ $alert['severity'] }}
                                </span>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2 text-xs">
                                <span class="rounded-full bg-white px-2.5 py-1 font-medium text-rose-700 dark:bg-gray-900 dark:text-rose-200">Value: {{ $alert['value'] }}</span>
                                <span class="rounded-full bg-white px-2.5 py-1 font-medium text-rose-700 dark:bg-gray-900 dark:text-rose-200">Threshold: {{ $alert['threshold'] }}</span>
                            </div>
                        </article>
                    @endforeach
                </div>
            </x-filament.ui.panel>
        @else
            <x-filament.ui.panel
                tone="emerald"
                eyebrow="Operational Alerts"
                title="Queue Risk Summary"
                badge="stable"
            >
                <div class="nova-empty-state border-emerald-200 bg-emerald-50/70 text-emerald-900 dark:border-emerald-800/40 dark:bg-emerald-900/10 dark:text-emerald-100">
                    No queue-risk alerts are active. If editors still report issues, inspect individual content records and prompt changes next.
                </div>
            </x-filament.ui.panel>
        @endif
    </div>
</x-filament-panels::page>
