<x-filament-panels::page>
    <div wire:poll.8s class="space-y-6">
        <section class="rounded-2xl border border-gray-200 bg-gradient-to-r from-slate-50 to-gray-100 p-5 shadow-sm dark:border-gray-800 dark:from-gray-900 dark:to-gray-900">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Queue Control Panel</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Track summary jobs, inspect failures, and cancel queued runs before they start.</p>
                </div>
                <div class="rounded-lg border border-gray-300 bg-white/80 px-3 py-1 text-xs font-medium text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    Auto refresh every 8s
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-4">
            <article class="rounded-xl border border-amber-200 bg-white p-4 shadow-sm dark:border-amber-800/50 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">Pending</p>
                <p class="mt-1 text-3xl font-bold text-amber-900 dark:text-amber-100">{{ $pendingCount }}</p>
                <p class="mt-2 text-xs text-gray-600 dark:text-gray-300">Waiting in queue and not started yet.</p>
            </article>
            <article class="rounded-xl border border-sky-200 bg-white p-4 shadow-sm dark:border-sky-800/50 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-sky-700 dark:text-sky-300">Generating</p>
                <p class="mt-1 text-3xl font-bold text-sky-900 dark:text-sky-100">{{ $generatingCount }}</p>
                <p class="mt-2 text-xs text-gray-600 dark:text-gray-300">Currently processed by queue worker.</p>
            </article>
            <article class="rounded-xl border border-rose-200 bg-white p-4 shadow-sm dark:border-rose-800/50 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-rose-700 dark:text-rose-300">Failed</p>
                <p class="mt-1 text-3xl font-bold text-rose-900 dark:text-rose-100">{{ $failedCount }}</p>
                <p class="mt-2 text-xs text-gray-600 dark:text-gray-300">Needs manual retry or configuration fix.</p>
            </article>
            <article class="rounded-xl border border-indigo-200 bg-white p-4 shadow-sm dark:border-indigo-800/50 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700 dark:text-indigo-300">Queue Depth</p>
                <p class="mt-1 text-3xl font-bold text-indigo-900 dark:text-indigo-100">{{ $queueDepth }}</p>
                <p class="mt-2 text-xs text-gray-600 dark:text-gray-300">Pending + generating jobs.</p>
            </article>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Operational Snapshot</p>
                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-200">Queue age and recent success/failure rates (last {{ $windowHours }}h).</p>
                </div>
            </div>
            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                <div class="rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-800">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Avg generation</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $avgGeneration }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-800">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Oldest pending age</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $oldestPendingAge }}</p>
                </div>
                <div class="rounded-lg bg-emerald-50 px-3 py-2 dark:bg-emerald-900/20">
                    <p class="text-xs text-emerald-700 dark:text-emerald-200">Success rate</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $recentSuccessRate !== null ? $recentSuccessRate.'%' : 'n/a' }}</p>
                </div>
                <div class="rounded-lg bg-rose-50 px-3 py-2 dark:bg-rose-900/20">
                    <p class="text-xs text-rose-700 dark:text-rose-200">Failure rate</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $recentFailureRate !== null ? $recentFailureRate.'%' : 'n/a' }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-800">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Completed runs</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $recentCompletedCount }}</p>
                </div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Queued Runs</h3>

                @if (count($pendingItems) === 0)
                    <div class="mt-4 rounded-lg border border-dashed border-gray-300 p-6 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
                        Queue is clean. No pending jobs.
                    </div>
                @else
                    <div class="mt-4 space-y-3">
                        @foreach ($pendingItems as $item)
                            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $item['title'] }}</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">#{{ $item['id'] }} · {{ $item['slug'] }} · {{ $item['updated'] }}</p>
                                    </div>
                                    <span class="shrink-0 rounded bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">pending</span>
                                </div>
                                <div class="mt-3 grid grid-cols-3 gap-2 text-xs">
                                    <div class="rounded bg-gray-50 px-2 py-1 dark:bg-gray-800">
                                        <p class="text-gray-500 dark:text-gray-400">Position</p>
                                        <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $item['queue_position'] }}</p>
                                    </div>
                                    <div class="rounded bg-gray-50 px-2 py-1 dark:bg-gray-800">
                                        <p class="text-gray-500 dark:text-gray-400">Waited</p>
                                        <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $item['wait'] }}</p>
                                    </div>
                                    <div class="rounded bg-gray-50 px-2 py-1 dark:bg-gray-800">
                                        <p class="text-gray-500 dark:text-gray-400">ETA</p>
                                        <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $item['eta'] }}</p>
                                    </div>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <x-filament::button
                                        size="xs"
                                        color="gray"
                                        tag="a"
                                        :href="\App\Filament\Resources\Contents\ContentResource::getUrl('view', ['record' => $item['id']])"
                                    >
                                        Open
                                    </x-filament::button>
                                    <x-filament::button
                                        size="xs"
                                        color="danger"
                                        wire:click="cancelQueued({{ $item['id'] }})"
                                    >
                                        Cancel
                                    </x-filament::button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>

            <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">In Progress</h3>

                @if (count($generatingItems) === 0)
                    <div class="mt-4 rounded-lg border border-dashed border-gray-300 p-6 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
                        No active generation right now.
                    </div>
                @else
                    <div class="mt-4 space-y-3">
                        @foreach ($generatingItems as $item)
                            <div class="rounded-lg border border-sky-200 p-3 dark:border-sky-800/50">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $item['title'] }}</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">#{{ $item['id'] }} · {{ $item['slug'] }} · model: {{ $item['model'] }}</p>
                                    </div>
                                    <span class="shrink-0 rounded bg-sky-100 px-2 py-0.5 text-xs font-medium text-sky-800 dark:bg-sky-900/40 dark:text-sky-200">generating</span>
                                </div>
                                <div class="mt-3 h-2 overflow-hidden rounded-full bg-sky-100 dark:bg-sky-900/40">
                                    <div class="h-full w-2/3 animate-pulse rounded-full bg-sky-500"></div>
                                </div>
                                <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                                    <div class="rounded bg-sky-50 px-2 py-1 dark:bg-sky-900/20">
                                        <p class="text-sky-700 dark:text-sky-200">Elapsed</p>
                                        <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $item['elapsed'] }}</p>
                                    </div>
                                    <div class="rounded bg-sky-50 px-2 py-1 dark:bg-sky-900/20">
                                        <p class="text-sky-700 dark:text-sky-200">ETA</p>
                                        <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $item['eta'] }}</p>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <x-filament::button
                                        size="xs"
                                        color="gray"
                                        tag="a"
                                        :href="\App\Filament\Resources\Contents\ContentResource::getUrl('view', ['record' => $item['id']])"
                                    >
                                        Open
                                    </x-filament::button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Recent Failed Runs</h3>

            @if (count($failedItems) === 0)
                <div class="mt-4 rounded-lg border border-dashed border-gray-300 p-6 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
                    No failed runs in the latest window.
                </div>
            @else
                <div class="mt-4 space-y-3">
                    @foreach ($failedItems as $item)
                        <details class="rounded-lg border border-rose-200 p-3 dark:border-rose-800/50">
                            <summary class="cursor-pointer text-sm font-semibold text-rose-900 dark:text-rose-200">
                                {{ $item['title'] }} <span class="text-xs font-normal text-gray-500 dark:text-gray-400">· #{{ $item['id'] }} · {{ $item['updated'] }}</span>
                            </summary>
                            <div class="mt-3 space-y-2 text-sm">
                                <p class="text-gray-700 dark:text-gray-200"><strong>Model:</strong> {{ $item['model'] }} · <strong>Latency:</strong> {{ $item['latency'] }}</p>
                                <p class="rounded bg-rose-50 p-2 text-rose-900 dark:bg-rose-900/20 dark:text-rose-100">{{ $item['last_error'] !== '' ? $item['last_error'] : 'No error message captured.' }}</p>
                                <x-filament::button
                                    size="xs"
                                    color="gray"
                                    tag="a"
                                    :href="\App\Filament\Resources\Contents\ContentResource::getUrl('view', ['record' => $item['id']])"
                                >
                                    Open Content
                                </x-filament::button>
                            </div>
                        </details>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-filament-panels::page>
