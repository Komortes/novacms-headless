<x-filament-panels::page>
    @php
        $okCount = collect($checks)->where('status', 'ok')->count();
        $warnCount = collect($checks)->where('status', 'warn')->count();
        $failCount = collect($checks)->where('status', 'fail')->count();
    @endphp

    <div class="space-y-5">
        <x-filament.ui.hero
            tone="sky"
            eyebrow="Runtime Control Surface"
            title="System Health"
            description="Infrastructure checks for database, queue, websockets, and local AI runtime."
        >
            <div class="flex flex-wrap gap-2">
                <span @class([
                    'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1',
                    'bg-emerald-400/15 text-emerald-100 ring-emerald-300/20' => $ok,
                    'bg-rose-400/15 text-rose-100 ring-rose-300/20' => ! $ok,
                ])>
                    {{ $ok ? 'All checks passed' : 'Runtime attention required' }}
                </span>
                <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-slate-200 ring-1 ring-white/10">
                    {{ \Illuminate\Support\Carbon::parse($generated_at)->diffForHumans() }}
                </span>
            </div>
        </x-filament.ui.hero>

        {{-- Metric strip --}}
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-filament.ui.metric-card
                label="Healthy"
                :value="$okCount"
                description="Components responding as expected."
                tone="emerald"
            />
            <x-filament.ui.metric-card
                label="Warnings"
                :value="$warnCount"
                description="Degraded, monitor before retrying."
                tone="amber"
            />
            <x-filament.ui.metric-card
                label="Failures"
                :value="$failCount"
                description="Blocking failures — fix first."
                tone="rose"
            />
            <x-filament.ui.metric-card
                label="Alerts"
                :value="count($alerts)"
                description="Queue lag and failure signals."
                tone="sky"
            />
        </section>

        {{-- Priority Actions — full width, items in a horizontal grid --}}
        <x-filament.ui.panel
            eyebrow="Priority Actions"
            title="What To Do Now"
        >
            <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($priorityActions as $action)
                    <div @class([
                        'rounded-[1.2rem] border px-4 py-3',
                        'border-rose-200 bg-rose-50/70 dark:border-rose-800/40 dark:bg-rose-900/10' => $action['tone'] === 'rose',
                        'border-amber-200 bg-amber-50/70 dark:border-amber-800/40 dark:bg-amber-900/10' => $action['tone'] === 'amber',
                        'border-sky-200 bg-sky-50/70 dark:border-sky-800/40 dark:bg-sky-900/10' => $action['tone'] === 'sky',
                        'border-emerald-200 bg-emerald-50/70 dark:border-emerald-800/40 dark:bg-emerald-900/10' => $action['tone'] === 'emerald',
                    ])>
                        <p class="text-sm font-semibold text-slate-950 dark:text-slate-100">{{ $action['title'] }}</p>
                        <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $action['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </x-filament.ui.panel>

        {{-- Status board — full width so each bucket gets proper space --}}
        <x-filament.ui.panel
            :tone="$failCount > 0 ? 'rose' : ($warnCount > 0 ? 'amber' : 'emerald')"
            eyebrow="Checks"
            title="Runtime Status Board"
            :badge="$ok ? 'healthy baseline' : 'needs action'"
        >
            <div class="mb-4 rounded-[1.1rem] border border-white/70 bg-white/60 px-4 py-3 text-sm font-medium text-slate-700 dark:border-white/10 dark:bg-slate-950/40 dark:text-slate-300">
                {{ $ok
                    ? 'All required services are reachable. Queue-side retries are safe.'
                    : 'At least one service is degraded. Fix runtime conditions before treating editor-side symptoms.' }}
            </div>

            <div class="grid gap-4 xl:grid-cols-3">
                @foreach ($statusBuckets as $bucket)
                    <section @class([
                        'rounded-[1.35rem] border p-4',
                        'border-rose-200 bg-rose-50/60 dark:border-rose-800/40 dark:bg-rose-900/10' => $bucket['tone'] === 'rose',
                        'border-amber-200 bg-amber-50/60 dark:border-amber-800/40 dark:bg-amber-900/10' => $bucket['tone'] === 'amber',
                        'border-emerald-200 bg-emerald-50/60 dark:border-emerald-800/40 dark:bg-emerald-900/10' => $bucket['tone'] === 'emerald',
                    ])>
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-950 dark:text-slate-100">{{ $bucket['label'] }}</p>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ $bucket['description'] }}</p>
                            </div>
                            <span @class([
                                'shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide',
                                'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200' => $bucket['tone'] === 'rose',
                                'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200' => $bucket['tone'] === 'amber',
                                'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200' => $bucket['tone'] === 'emerald',
                            ])>
                                {{ count($bucket['items']) }}
                            </span>
                        </div>

                        <div class="mt-3 space-y-2">
                            @forelse ($bucket['items'] as $check)
                                <article class="nova-signal-card nova-signal-card-compact bg-white/90 dark:bg-slate-950/75">
                                    <div class="flex items-start justify-between gap-3">
                                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $check['component'] }}</p>
                                        <span @class([
                                            'shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide',
                                            'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200' => $check['status'] === 'ok',
                                            'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200' => $check['status'] === 'warn',
                                            'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200' => $check['status'] === 'fail',
                                        ])>
                                            {{ $check['status'] }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xs leading-5 text-slate-600 dark:text-slate-300">{{ $check['message'] }}</p>

                                    @if (($check['meta'] ?? []) !== [])
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @foreach (($check['meta'] ?? []) as $key => $value)
                                                <span class="inline-flex flex-col rounded-lg border border-slate-200/80 bg-white/80 px-2.5 py-1.5 dark:border-slate-800/60 dark:bg-slate-950/60">
                                                    <span class="text-[9px] font-semibold uppercase tracking-wider text-slate-400">{{ str_replace('_', ' ', (string) $key) }}</span>
                                                    <span class="mt-0.5 text-xs font-semibold text-slate-800 dark:text-slate-100">
                                                        @if (is_scalar($value) || $value === null)
                                                            {{ $value === null ? 'n/a' : (string) $value }}
                                                        @else
                                                            {{ json_encode($value, JSON_UNESCAPED_SLASHES) }}
                                                        @endif
                                                    </span>
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </article>
                            @empty
                                <div class="nova-empty-state py-4 text-sm">{{ $bucket['empty'] }}</div>
                            @endforelse
                        </div>
                    </section>
                @endforeach
            </div>
        </x-filament.ui.panel>

        {{-- Alerts --}}
        @if (count($alerts) > 0)
            <x-filament.ui.panel
                tone="rose"
                eyebrow="Operational Alerts"
                title="Queue Risk Summary"
                :badge="count($alerts) . ' active'"
            >
                <div class="grid gap-3 md:grid-cols-2">
                    @foreach ($alerts as $alert)
                        <article class="rounded-2xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-800/40 dark:bg-rose-900/15">
                            <div class="flex items-start justify-between gap-3">
                                <p class="text-sm font-semibold text-rose-950 dark:text-rose-100">{{ $alert['title'] }}</p>
                                <span class="shrink-0 rounded-full bg-white/80 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-rose-700 dark:bg-rose-950/40 dark:text-rose-200">
                                    {{ $alert['severity'] }}
                                </span>
                            </div>
                            <p class="mt-2 text-sm leading-6 text-rose-800 dark:text-rose-200">{{ $alert['message'] }}</p>
                            <div class="mt-3 flex flex-wrap gap-2 text-xs">
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
                    No queue-risk alerts are active.
                </div>
            </x-filament.ui.panel>
        @endif
    </div>
</x-filament-panels::page>
