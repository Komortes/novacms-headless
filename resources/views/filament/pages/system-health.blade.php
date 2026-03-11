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
            description="Infrastructure checks for database, queue, websockets, and local AI runtime. Use this page to separate editor-side problems from environment-level failures before retrying jobs."
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

        <section class="grid gap-6 xl:grid-cols-[1.7fr_0.9fr]">
            <x-filament.ui.panel
                eyebrow="Checks"
                title="Component Status"
                :badge="count($checks) . ' checks'"
            >
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach ($checks as $check)
                        <article @class([
                            'rounded-2xl border p-4',
                            'border-emerald-200 bg-emerald-50/60 dark:border-emerald-800/40 dark:bg-emerald-900/10' => $check['status'] === 'ok',
                            'border-amber-200 bg-amber-50/60 dark:border-amber-800/40 dark:bg-amber-900/10' => $check['status'] === 'warn',
                            'border-rose-200 bg-rose-50/60 dark:border-rose-800/40 dark:bg-rose-900/10' => $check['status'] === 'fail',
                        ])>
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $check['component'] }}</p>
                                    <p class="mt-2 text-sm leading-6 text-gray-700 dark:text-gray-200">{{ $check['message'] }}</p>
                                </div>
                                <span @class([
                                    'shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide',
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200' => $check['status'] === 'ok',
                                    'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200' => $check['status'] === 'warn',
                                    'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200' => $check['status'] === 'fail',
                                ])>
                                    {{ $check['status'] }}
                                </span>
                            </div>

                            @if (($check['meta'] ?? []) !== [])
                                <div class="mt-4 grid gap-2 sm:grid-cols-2">
                                    @foreach (($check['meta'] ?? []) as $key => $value)
                                        <div class="rounded-xl bg-white/80 px-3 py-2 text-xs dark:bg-gray-950/60">
                                            <p class="font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ str_replace('_', ' ', (string) $key) }}</p>
                                            <p class="mt-1 break-words text-gray-900 dark:text-gray-100">
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
                    @endforeach
                </div>
            </x-filament.ui.panel>

            <div class="space-y-6">
                <x-filament.ui.panel
                    eyebrow="Response Order"
                    title="Where To Act First"
                >
                    <ol class="space-y-3 text-sm text-gray-700 dark:text-gray-200">
                        <li class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <span class="font-semibold text-gray-900 dark:text-gray-100">1. Fix infrastructure failures</span>
                            <p class="mt-1">Database, Redis, or Ollama failures invalidate editor-side retries.</p>
                        </li>
                        <li class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <span class="font-semibold text-gray-900 dark:text-gray-100">2. Re-check Queue Center</span>
                            <p class="mt-1">Once runtime is healthy, inspect pending age, failures, and stale jobs.</p>
                        </li>
                        <li class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <span class="font-semibold text-gray-900 dark:text-gray-100">3. Retry failed records</span>
                            <p class="mt-1">Return to failed runs only after provider and worker conditions are stable.</p>
                        </li>
                    </ol>
                </x-filament.ui.panel>

                <x-filament.ui.panel
                    eyebrow="Signals To Watch"
                    title="Common Runtime Failure Modes"
                >
                    <div class="space-y-3 text-sm text-gray-700 dark:text-gray-200">
                        <div class="rounded-2xl border border-gray-200 px-4 py-3 dark:border-gray-700">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">Ollama unavailable</p>
                            <p class="mt-1">Expect summary failures and empty embedding runs until the local model service responds again.</p>
                        </div>
                        <div class="rounded-2xl border border-gray-200 px-4 py-3 dark:border-gray-700">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">Redis or Horizon degraded</p>
                            <p class="mt-1">Pending queue depth will grow even if content updates still succeed in the UI.</p>
                        </div>
                        <div class="rounded-2xl border border-gray-200 px-4 py-3 dark:border-gray-700">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">Reverb issues</p>
                            <p class="mt-1">Jobs may finish correctly, but status transitions will stop updating in real time.</p>
                        </div>
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
        @endif
    </div>
</x-filament-panels::page>
