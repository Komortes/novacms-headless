<x-filament-panels::page>
    <div wire:poll.8s class="space-y-6">
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 shadow-sm dark:border-slate-800">
            <div class="grid gap-6 px-6 py-6 lg:grid-cols-[1.65fr_0.95fr] lg:px-8">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] text-indigo-100">
                        Queue Operations
                    </div>
                    <h2 class="mt-4 text-2xl font-semibold tracking-tight text-white">Queue Control Panel</h2>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-200/90">
                        Watch queue pressure, inspect active work, and decide whether the next action is to cancel,
                        wait, or move to system-level troubleshooting. This page is meant to be operational, not just descriptive.
                    </p>

                    <div class="mt-5 flex flex-wrap gap-2">
                        <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-slate-200 ring-1 ring-white/10">
                            Auto refresh every 8s
                        </span>
                        <span class="inline-flex items-center rounded-full bg-amber-400/15 px-3 py-1 text-xs font-medium text-amber-100 ring-1 ring-amber-300/20">
                            Pending {{ $pendingCount }} / Failed {{ $failedCount }}
                        </span>
                    </div>
                </div>

                <aside class="rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-300">Operator Playbook</p>
                    <ol class="mt-4 space-y-3 text-sm text-slate-200">
                        <li class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                            <span class="font-semibold text-white">1. Check queue pressure</span>
                            <p class="mt-1 text-slate-300">Use queue depth, pending age, and failure rate to decide whether this is a temporary spike or a broken runtime.</p>
                        </li>
                        <li class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                            <span class="font-semibold text-white">2. Inspect the bucket</span>
                            <p class="mt-1 text-slate-300">Pending means waiting, generating means active work, failed means manual intervention.</p>
                        </li>
                        <li class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                            <span class="font-semibold text-white">3. Escalate correctly</span>
                            <p class="mt-1 text-slate-300">If failures cluster, jump to System Health before triggering more retries.</p>
                        </li>
                    </ol>
                </aside>
            </div>
        </section>

        @if (count($alerts) > 0)
            <section class="rounded-2xl border border-rose-200 bg-white p-5 shadow-sm dark:border-rose-800/40 dark:bg-gray-900">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-rose-600 dark:text-rose-300">Queue Alerts</p>
                        <h3 class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">Escalation Signals</h3>
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

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-amber-200 bg-white p-5 shadow-sm dark:border-amber-800/40 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">Pending</p>
                <p class="mt-2 text-3xl font-semibold text-amber-950 dark:text-amber-100">{{ $pendingCount }}</p>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Queued and not yet claimed by a worker.</p>
            </article>
            <article class="rounded-2xl border border-sky-200 bg-white p-5 shadow-sm dark:border-sky-800/40 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-sky-700 dark:text-sky-300">Generating</p>
                <p class="mt-2 text-3xl font-semibold text-sky-950 dark:text-sky-100">{{ $generatingCount }}</p>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Worker time currently spent on active summary runs.</p>
            </article>
            <article class="rounded-2xl border border-rose-200 bg-white p-5 shadow-sm dark:border-rose-800/40 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-rose-700 dark:text-rose-300">Failed</p>
                <p class="mt-2 text-3xl font-semibold text-rose-950 dark:text-rose-100">{{ $failedCount }}</p>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Runs that need error review before retry or config changes.</p>
            </article>
            <article class="rounded-2xl border border-indigo-200 bg-white p-5 shadow-sm dark:border-indigo-800/40 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700 dark:text-indigo-300">Queue Depth</p>
                <p class="mt-2 text-3xl font-semibold text-indigo-950 dark:text-indigo-100">{{ $queueDepth }}</p>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Total pressure across pending and generating buckets.</p>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.5fr_0.9fr]">
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Operational Snapshot</p>
                        <h3 class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">Recent Throughput</h3>
                    </div>
                    <div class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        last {{ $windowHours }}h
                    </div>
                </div>

                <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                    <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Avg generation</p>
                        <p class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $avgGeneration }}</p>
                    </div>
                    <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Oldest pending</p>
                        <p class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $oldestPendingAge }}</p>
                    </div>
                    <div class="rounded-2xl bg-emerald-50 px-4 py-3 dark:bg-emerald-900/15">
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Success rate</p>
                        <p class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $recentSuccessRate !== null ? $recentSuccessRate.'%' : 'n/a' }}</p>
                    </div>
                    <div class="rounded-2xl bg-rose-50 px-4 py-3 dark:bg-rose-900/15">
                        <p class="text-xs font-semibold uppercase tracking-wide text-rose-700 dark:text-rose-300">Failure rate</p>
                        <p class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $recentFailureRate !== null ? $recentFailureRate.'%' : 'n/a' }}</p>
                    </div>
                    <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Completed runs</p>
                        <p class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $recentCompletedCount }}</p>
                    </div>
                </div>
            </article>

            <aside class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">State Legend</p>
                <div class="mt-4 space-y-3 text-sm text-gray-700 dark:text-gray-200">
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-800/40 dark:bg-amber-900/10">
                        <p class="font-semibold text-gray-900 dark:text-gray-100">Pending</p>
                        <p class="mt-1">Safe to wait. Cancel only when the run is obsolete or queued by mistake.</p>
                    </div>
                    <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 dark:border-sky-800/40 dark:bg-sky-900/10">
                        <p class="font-semibold text-gray-900 dark:text-gray-100">Generating</p>
                        <p class="mt-1">This is active worker time. Re-queuing now usually adds noise, not clarity.</p>
                    </div>
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 dark:border-rose-800/40 dark:bg-rose-900/10">
                        <p class="font-semibold text-gray-900 dark:text-gray-100">Failed</p>
                        <p class="mt-1">Read the error first. If failures cluster, move to System Health before retrying content.</p>
                    </div>
                </div>
            </aside>
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Bucket</p>
                        <h3 class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">Queued Runs</h3>
                    </div>
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">{{ count($pendingItems) }}</span>
                </div>

                @if (count($pendingItems) === 0)
                    <div class="mt-5 rounded-2xl border border-dashed border-gray-300 p-6 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
                        Queue is clean. No pending jobs are waiting for pickup.
                    </div>
                @else
                    <div class="mt-5 space-y-3">
                        @foreach ($pendingItems as $item)
                            <article class="rounded-2xl border border-gray-200 p-4 dark:border-gray-700">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $item['title'] }}</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">#{{ $item['id'] }} · {{ $item['slug'] }} · {{ $item['updated'] }}</p>
                                    </div>
                                    <span class="shrink-0 rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">pending</span>
                                </div>

                                <div class="mt-4 grid gap-2 sm:grid-cols-3 text-xs">
                                    <div class="rounded-xl bg-gray-50 px-3 py-2 dark:bg-gray-800">
                                        <p class="font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Position</p>
                                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $item['queue_position'] }}</p>
                                    </div>
                                    <div class="rounded-xl bg-gray-50 px-3 py-2 dark:bg-gray-800">
                                        <p class="font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Waited</p>
                                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $item['wait'] }}</p>
                                    </div>
                                    <div class="rounded-xl bg-gray-50 px-3 py-2 dark:bg-gray-800">
                                        <p class="font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">ETA</p>
                                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $item['eta'] }}</p>
                                    </div>
                                </div>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    <x-filament::button
                                        size="xs"
                                        color="gray"
                                        tag="a"
                                        :href="\App\Filament\Resources\Contents\ContentResource::getUrl('view', ['record' => $item['id']])"
                                    >
                                        Open Record
                                    </x-filament::button>
                                    <x-filament::button
                                        size="xs"
                                        color="danger"
                                        wire:click="cancelQueued({{ $item['id'] }})"
                                    >
                                        Cancel Queued Run
                                    </x-filament::button>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </article>

            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Bucket</p>
                        <h3 class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">In Progress</h3>
                    </div>
                    <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-800 dark:bg-sky-900/30 dark:text-sky-200">{{ count($generatingItems) }}</span>
                </div>

                @if (count($generatingItems) === 0)
                    <div class="mt-5 rounded-2xl border border-dashed border-gray-300 p-6 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
                        No active generation right now.
                    </div>
                @else
                    <div class="mt-5 space-y-3">
                        @foreach ($generatingItems as $item)
                            <article class="rounded-2xl border border-sky-200 p-4 dark:border-sky-800/40">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $item['title'] }}</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">#{{ $item['id'] }} · {{ $item['slug'] }} · model {{ $item['model'] }}</p>
                                    </div>
                                    <span class="shrink-0 rounded-full bg-sky-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-sky-800 dark:bg-sky-900/30 dark:text-sky-200">generating</span>
                                </div>

                                <div class="mt-4 h-2 overflow-hidden rounded-full bg-sky-100 dark:bg-sky-900/30">
                                    <div class="h-full w-2/3 animate-pulse rounded-full bg-sky-500"></div>
                                </div>

                                <div class="mt-4 grid gap-2 sm:grid-cols-2 text-xs">
                                    <div class="rounded-xl bg-sky-50 px-3 py-2 dark:bg-sky-900/20">
                                        <p class="font-semibold uppercase tracking-wide text-sky-700 dark:text-sky-300">Elapsed</p>
                                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $item['elapsed'] }}</p>
                                    </div>
                                    <div class="rounded-xl bg-sky-50 px-3 py-2 dark:bg-sky-900/20">
                                        <p class="font-semibold uppercase tracking-wide text-sky-700 dark:text-sky-300">ETA</p>
                                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $item['eta'] }}</p>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <x-filament::button
                                        size="xs"
                                        color="gray"
                                        tag="a"
                                        :href="\App\Filament\Resources\Contents\ContentResource::getUrl('view', ['record' => $item['id']])"
                                    >
                                        Open Record
                                    </x-filament::button>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </article>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Bucket</p>
                    <h3 class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">Recent Failed Runs</h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">The goal here is diagnosis first: identify whether the failure belongs to content, queue pressure, or the AI runtime.</p>
                </div>
                <div class="rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-800 dark:bg-rose-900/30 dark:text-rose-200">
                    {{ count($failedItems) }} shown
                </div>
            </div>

            @if (count($failedItems) === 0)
                <div class="mt-5 rounded-2xl border border-dashed border-gray-300 p-6 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
                    No failed runs in the latest window.
                </div>
            @else
                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    @foreach ($failedItems as $item)
                        <article class="rounded-2xl border border-rose-200 bg-rose-50/50 p-4 dark:border-rose-800/40 dark:bg-rose-900/10">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $item['title'] }}</p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">#{{ $item['id'] }} · {{ $item['slug'] }} · {{ $item['updated'] }}</p>
                                </div>
                                <span class="shrink-0 rounded-full bg-rose-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-rose-800 dark:bg-rose-900/30 dark:text-rose-200">failed</span>
                            </div>

                            <div class="mt-4 grid gap-2 sm:grid-cols-2 text-xs">
                                <div class="rounded-xl bg-white px-3 py-2 dark:bg-gray-900">
                                    <p class="font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Model</p>
                                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $item['model'] }}</p>
                                </div>
                                <div class="rounded-xl bg-white px-3 py-2 dark:bg-gray-900">
                                    <p class="font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Latency</p>
                                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $item['latency'] }}</p>
                                </div>
                            </div>

                            <div class="mt-4 rounded-2xl border border-rose-200 bg-white px-4 py-3 text-sm leading-6 text-rose-900 dark:border-rose-800/40 dark:bg-gray-900 dark:text-rose-100">
                                {{ $item['last_error'] !== '' ? $item['last_error'] : 'No error message captured.' }}
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <x-filament::button
                                    size="xs"
                                    color="gray"
                                    tag="a"
                                    :href="\App\Filament\Resources\Contents\ContentResource::getUrl('view', ['record' => $item['id']])"
                                >
                                    Open Content
                                </x-filament::button>
                                <x-filament::button
                                    size="xs"
                                    color="gray"
                                    tag="a"
                                    :href="\App\Filament\Pages\SystemHealth::getUrl()"
                                >
                                    Check System Health
                                </x-filament::button>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-filament-panels::page>
