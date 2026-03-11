<x-filament-panels::page>
    @php
        $okCount = collect($checks)->where('status', 'ok')->count();
        $warnCount = collect($checks)->where('status', 'warn')->count();
        $failCount = collect($checks)->where('status', 'fail')->count();
    @endphp

    <div class="space-y-6">
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-sky-950 shadow-sm dark:border-slate-800">
            <div class="grid gap-6 px-6 py-6 lg:grid-cols-[1.7fr_0.9fr] lg:px-8">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] text-sky-100">
                        Runtime Control Surface
                    </div>
                    <h2 class="mt-4 text-2xl font-semibold tracking-tight text-white">System Health</h2>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-200/90">
                        Infrastructure checks for database, queue, websockets, and local AI runtime.
                        Use this page to separate editor-facing problems from environment-level failures before retrying jobs.
                    </p>

                    <div class="mt-5 flex flex-wrap gap-2">
                        <span @class([
                            'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold',
                            'bg-emerald-400/15 text-emerald-100 ring-1 ring-emerald-300/20' => $ok,
                            'bg-rose-400/15 text-rose-100 ring-1 ring-rose-300/20' => ! $ok,
                        ])>
                            {{ $ok ? 'All required checks passed' : 'Runtime attention required' }}
                        </span>
                        <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-slate-200 ring-1 ring-white/10">
                            Last check {{ \Illuminate\Support\Carbon::parse($generated_at)->diffForHumans() }}
                        </span>
                    </div>
                </div>

                <aside class="rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-300">Operator Guide</p>
                    <div class="mt-4 space-y-3 text-sm text-slate-200">
                        <div class="rounded-2xl border border-emerald-400/15 bg-emerald-400/10 px-4 py-3">
                            <p class="font-semibold text-emerald-100">Healthy</p>
                            <p class="mt-1 text-emerald-50/80">Queue and runtime are ready for normal summary generation and retries.</p>
                        </div>
                        <div class="rounded-2xl border border-amber-300/15 bg-amber-300/10 px-4 py-3">
                            <p class="font-semibold text-amber-100">Warning</p>
                            <p class="mt-1 text-amber-50/80">Something is degraded. Jobs may still run, but latency or failure risk is rising.</p>
                        </div>
                        <div class="rounded-2xl border border-rose-300/15 bg-rose-300/10 px-4 py-3">
                            <p class="font-semibold text-rose-100">Failure</p>
                            <p class="mt-1 text-rose-50/80">Resolve infrastructure first, then return to Queue Center for retries.</p>
                        </div>
                    </div>
                </aside>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm dark:border-emerald-800/40 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Healthy Checks</p>
                <p class="mt-2 text-3xl font-semibold text-emerald-950 dark:text-emerald-100">{{ $okCount }}</p>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Components responding as expected.</p>
            </article>
            <article class="rounded-2xl border border-amber-200 bg-white p-5 shadow-sm dark:border-amber-800/40 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">Warnings</p>
                <p class="mt-2 text-3xl font-semibold text-amber-950 dark:text-amber-100">{{ $warnCount }}</p>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Degraded checks that should be monitored before queue pressure grows.</p>
            </article>
            <article class="rounded-2xl border border-rose-200 bg-white p-5 shadow-sm dark:border-rose-800/40 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-rose-700 dark:text-rose-300">Failures</p>
                <p class="mt-2 text-3xl font-semibold text-rose-950 dark:text-rose-100">{{ $failCount }}</p>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Blocking failures that should be fixed before retrying failed content runs.</p>
            </article>
            <article class="rounded-2xl border border-sky-200 bg-white p-5 shadow-sm dark:border-sky-800/40 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-sky-700 dark:text-sky-300">Alerts</p>
                <p class="mt-2 text-3xl font-semibold text-sky-950 dark:text-sky-100">{{ count($alerts) }}</p>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Operational thresholds derived from queue lag and failure growth.</p>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.7fr_0.9fr]">
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Checks</p>
                        <h3 class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">Component Status</h3>
                    </div>
                    <div class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        {{ count($checks) }} checks
                    </div>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
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
                                        <div class="rounded-xl bg-white/70 px-3 py-2 text-xs dark:bg-gray-900/50">
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
            </article>

            <aside class="space-y-6">
                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Response Order</p>
                    <ol class="mt-4 space-y-3 text-sm text-gray-700 dark:text-gray-200">
                        <li class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <span class="font-semibold text-gray-900 dark:text-gray-100">1. Fix infrastructure failures</span>
                            <p class="mt-1">Database, Redis, or Ollama failures will invalidate editor-side retries.</p>
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
                </section>

                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Signals To Watch</p>
                    <div class="mt-4 space-y-3 text-sm text-gray-700 dark:text-gray-200">
                        <div class="rounded-2xl border border-gray-200 px-4 py-3 dark:border-gray-700">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">Ollama unavailable</p>
                            <p class="mt-1">Expect summary failures and empty embedding runs until the local model service responds again.</p>
                        </div>
                        <div class="rounded-2xl border border-gray-200 px-4 py-3 dark:border-gray-700">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">Redis/Horizon degraded</p>
                            <p class="mt-1">Pending queue depth will grow even if content updates still succeed in the UI.</p>
                        </div>
                        <div class="rounded-2xl border border-gray-200 px-4 py-3 dark:border-gray-700">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">Reverb issues</p>
                            <p class="mt-1">Jobs may finish correctly, but status transitions will stop updating in real time.</p>
                        </div>
                    </div>
                </section>
            </aside>
        </section>

        @if (count($alerts) > 0)
            <section class="rounded-2xl border border-rose-200 bg-white p-5 shadow-sm dark:border-rose-800/40 dark:bg-gray-900">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-rose-600 dark:text-rose-300">Operational Alerts</p>
                        <h3 class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">Queue Risk Summary</h3>
                    </div>
                    <div class="rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-800 dark:bg-rose-900/30 dark:text-rose-200">
                        {{ count($alerts) }} active
                    </div>
                </div>

                <div class="mt-5 grid gap-3 lg:grid-cols-2">
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
            </section>
        @endif
    </div>
</x-filament-panels::page>
