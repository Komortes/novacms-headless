<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-2xl border border-gray-200 bg-gradient-to-r from-slate-50 to-gray-100 p-5 shadow-sm dark:border-gray-800 dark:from-gray-900 dark:to-gray-900">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Runtime Health</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        Last check: {{ \Illuminate\Support\Carbon::parse($generated_at)->diffForHumans() }}
                    </p>
                </div>
                <span @class([
                    'rounded-lg px-3 py-1 text-xs font-semibold',
                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200' => $ok,
                    'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200' => ! $ok,
                ])>
                    {{ $ok ? 'All checks healthy' : 'Some checks failed' }}
                </span>
            </div>
        </section>

        @if (count($alerts) > 0)
            <section class="rounded-xl border border-rose-200 bg-white p-4 shadow-sm dark:border-rose-800/50 dark:bg-gray-900">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-rose-700 dark:text-rose-300">Operational Alerts</h3>
                <div class="mt-3 space-y-2">
                    @foreach ($alerts as $alert)
                        <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm dark:border-rose-800/50 dark:bg-rose-900/20">
                            <p class="font-semibold text-rose-900 dark:text-rose-100">{{ $alert['title'] }}</p>
                            <p class="text-rose-800 dark:text-rose-200">{{ $alert['message'] }}</p>
                            <p class="mt-1 text-xs text-rose-700 dark:text-rose-300">
                                Value: {{ $alert['value'] }} | Threshold: {{ $alert['threshold'] }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($checks as $check)
                <article @class([
                    'rounded-xl border bg-white p-4 shadow-sm dark:bg-gray-900',
                    'border-emerald-200 dark:border-emerald-800/50' => $check['status'] === 'ok',
                    'border-amber-200 dark:border-amber-800/50' => $check['status'] === 'warn',
                    'border-rose-200 dark:border-rose-800/50' => $check['status'] === 'fail',
                ])>
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $check['component'] }}</p>
                        <span @class([
                            'rounded px-2 py-0.5 text-xs font-semibold uppercase',
                            'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200' => $check['status'] === 'ok',
                            'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200' => $check['status'] === 'warn',
                            'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200' => $check['status'] === 'fail',
                        ])>
                            {{ $check['status'] }}
                        </span>
                    </div>
                    <p class="mt-2 text-sm text-gray-700 dark:text-gray-200">{{ $check['message'] }}</p>

                    @if (($check['meta'] ?? []) !== [])
                        <pre class="mt-3 overflow-auto rounded-lg bg-gray-50 p-2 text-xs text-gray-700 dark:bg-gray-800 dark:text-gray-200">{{ json_encode($check['meta'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    @endif
                </article>
            @endforeach
        </section>
    </div>
</x-filament-panels::page>

