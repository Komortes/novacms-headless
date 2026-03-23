<x-filament-panels::page>
    <div wire:poll.8s class="space-y-6">
        <x-filament.ui.hero
            tone="slate"
            eyebrow="Queue Operations"
            title="Queue Control Panel"
            description="Watch AI summarization queue pressure, inspect active work, and decide whether the next action is to cancel, wait, or move to runtime troubleshooting."
        >
            <x-slot:aside>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-300">Operator Playbook</p>
                <ol class="mt-4 space-y-3 text-sm text-slate-200">
                    <li class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                        <span class="font-semibold text-white">1. Check queue pressure</span>
                        <p class="mt-1 text-slate-300">Use queue depth, pending age, and failure rate to decide whether this is a spike or a broken runtime.</p>
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
            </x-slot:aside>

            <div class="mt-5 flex flex-wrap gap-2">
                <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-slate-200 ring-1 ring-white/10">
                    Auto refresh every 8s
                </span>
                <span class="inline-flex items-center rounded-full bg-amber-400/15 px-3 py-1 text-xs font-medium text-amber-100 ring-1 ring-amber-300/20">
                    Pending {{ $pendingCount }} / Failed {{ $failedCount }}
                </span>
            </div>
        </x-filament.ui.hero>

        @if (count($alerts) > 0)
            <x-filament.ui.panel
                tone="rose"
                eyebrow="Queue Alerts"
                title="Escalation Signals"
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

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-filament.ui.metric-card
                label="Pending"
                :value="$pendingCount"
                description="Queued and not yet claimed by a worker."
                tone="amber"
            />
            <x-filament.ui.metric-card
                label="Generating"
                :value="$generatingCount"
                description="Worker time currently spent on active summary runs."
                tone="sky"
            />
            <x-filament.ui.metric-card
                label="Failed"
                :value="$failedCount"
                description="Runs that need error review before retry."
                tone="rose"
            />
            <x-filament.ui.metric-card
                label="Queue Depth"
                :value="$queueDepth"
                description="Total pressure across pending and generating buckets."
                tone="indigo"
            />
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.5fr_0.9fr]">
            <x-filament.ui.panel
                :tone="$pressureState['tone']"
                eyebrow="Operational Snapshot"
                title="Pressure Map"
                :badge="$pressureState['label']"
            >
                <div class="rounded-[1.35rem] border border-white/70 bg-white/65 px-5 py-4 dark:border-white/10 dark:bg-slate-950/45">
                    <p class="text-sm font-semibold text-slate-950 dark:text-slate-100">{{ $pressureState['description'] }}</p>
                    <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                        Oldest pending run has been waiting {{ $oldestPendingAge }}, average generation time is {{ $avgGeneration }}, and the latest {{ $windowHours }}h window completed {{ $recentCompletedCount }} runs.
                    </p>
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ($bucketMix as $stat)
                        <div class="nova-mini-stat">
                            <p class="nova-mini-stat-label">{{ $stat['label'] }}</p>
                            <p class="nova-mini-stat-value">{{ $stat['value'] }}</p>
                            <p class="nova-mini-stat-description">{{ $stat['description'] }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <div class="nova-mini-stat">
                        <p class="nova-mini-stat-label">Avg generation</p>
                        <p class="nova-mini-stat-value">{{ $avgGeneration }}</p>
                        <p class="nova-mini-stat-description">Use this to judge whether pending ETA estimates are realistic.</p>
                    </div>
                    <div class="nova-mini-stat">
                        <p class="nova-mini-stat-label">Oldest pending</p>
                        <p class="nova-mini-stat-value">{{ $oldestPendingAge }}</p>
                        <p class="nova-mini-stat-description">{{ $stalePendingCount > 0 ? $stalePendingCount . ' items are beyond the ' . $lagThresholdMinutes . 'm threshold.' : 'No stale pending items right now.' }}</p>
                    </div>
                    <div class="nova-mini-stat">
                        <p class="nova-mini-stat-label">Success rate</p>
                        <p class="nova-mini-stat-value">{{ $recentSuccessRate !== null ? $recentSuccessRate . '%' : 'n/a' }}</p>
                        <p class="nova-mini-stat-description">Healthy queue windows should keep this comfortably above failure rate.</p>
                    </div>
                    <div class="nova-mini-stat">
                        <p class="nova-mini-stat-label">Failure rate</p>
                        <p class="nova-mini-stat-value">{{ $recentFailureRate !== null ? $recentFailureRate . '%' : 'n/a' }}</p>
                        <p class="nova-mini-stat-description">If this climbs, read failed runs before queueing more work.</p>
                    </div>
                </div>
            </x-filament.ui.panel>

            <div class="space-y-6">
                <x-filament.ui.panel
                    eyebrow="Decision Routes"
                    title="Where To Move Next"
                    description="Treat Queue Center as the routing layer between content operations, runtime health, and AI configuration."
                >
                    <div class="space-y-3">
                        @foreach ($recommendedRoutes as $route)
                            <a href="{{ $route['href'] }}" class="nova-link-tile">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-950 dark:text-slate-100">{{ $route['label'] }}</p>
                                        <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $route['description'] }}</p>
                                    </div>
                                    <span @class([
                                        'rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide',
                                        'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-200' => $route['tone'] === 'indigo',
                                        'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200' => $route['tone'] === 'amber',
                                        'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-200' => $route['tone'] === 'rose',
                                        'bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-200' => $route['tone'] === 'sky',
                                        'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200' => $route['tone'] === 'emerald',
                                    ])>
                                        {{ $route['badge'] }}
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </x-filament.ui.panel>

                <x-filament.ui.panel
                    eyebrow="State Legend"
                    title="How To Read The Buckets"
                >
                    <div class="space-y-3 text-sm text-gray-700 dark:text-gray-200">
                        <div class="nova-signal-card border-amber-200 bg-amber-50/70 dark:border-amber-800/40 dark:bg-amber-900/10">
                            <p class="nova-signal-card-title">Pending</p>
                            <p class="nova-signal-card-copy">Safe to wait. Cancel only when the run is obsolete or queued by mistake.</p>
                        </div>
                        <div class="nova-signal-card border-sky-200 bg-sky-50/70 dark:border-sky-800/40 dark:bg-sky-900/10">
                            <p class="nova-signal-card-title">Generating</p>
                            <p class="nova-signal-card-copy">This is active worker time. Re-queuing now usually adds noise, not clarity.</p>
                        </div>
                        <div class="nova-signal-card border-rose-200 bg-rose-50/70 dark:border-rose-800/40 dark:bg-rose-900/10">
                            <p class="nova-signal-card-title">Failed</p>
                            <p class="nova-signal-card-copy">Read the error first. If failures cluster, move to System Health before retrying content.</p>
                        </div>
                    </div>
                </x-filament.ui.panel>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <x-filament.ui.panel
                eyebrow="Bucket"
                title="Queued Runs"
                :badge="$stalePendingCount > 0 ? count($pendingItems) . ' shown · ' . $stalePendingCount . ' stale' : count($pendingItems) . ' shown'"
            >
                @if (count($pendingItems) === 0)
                    <div class="nova-empty-state">
                        Queue is clean. No pending jobs are waiting for pickup.
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($pendingItems as $item)
                            <article class="rounded-2xl border border-gray-200 p-4 dark:border-gray-700">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $item['title'] }}</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">#{{ $item['id'] }} · {{ $item['slug'] }} · {{ $item['updated'] }}</p>
                                    </div>
                                    <span class="shrink-0 rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">pending</span>
                                </div>

                                <div class="mt-4 grid gap-2 text-xs sm:grid-cols-3">
                                    <div class="nova-mini-stat">
                                        <p class="nova-mini-stat-label">Position</p>
                                        <p class="nova-mini-stat-value text-base">{{ $item['queue_position'] }}</p>
                                    </div>
                                    <div class="nova-mini-stat">
                                        <p class="nova-mini-stat-label">Waited</p>
                                        <p class="nova-mini-stat-value text-base">{{ $item['wait'] }}</p>
                                    </div>
                                    <div class="nova-mini-stat">
                                        <p class="nova-mini-stat-label">ETA</p>
                                        <p class="nova-mini-stat-value text-base">{{ $item['eta'] }}</p>
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
            </x-filament.ui.panel>

            <x-filament.ui.panel
                eyebrow="Bucket"
                title="In Progress"
                :badge="count($generatingItems) . ' active'"
            >
                @if (count($generatingItems) === 0)
                    <div class="nova-empty-state">
                        No active generation right now.
                    </div>
                @else
                    <div class="space-y-3">
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

                                <div class="mt-4 grid gap-2 text-xs sm:grid-cols-2">
                                    <div class="nova-mini-stat border-sky-200 bg-sky-50/80 dark:border-sky-800/40 dark:bg-sky-900/15">
                                        <p class="nova-mini-stat-label text-sky-700 dark:text-sky-300">Elapsed</p>
                                        <p class="nova-mini-stat-value text-base">{{ $item['elapsed'] }}</p>
                                    </div>
                                    <div class="nova-mini-stat border-sky-200 bg-sky-50/80 dark:border-sky-800/40 dark:bg-sky-900/15">
                                        <p class="nova-mini-stat-label text-sky-700 dark:text-sky-300">ETA</p>
                                        <p class="nova-mini-stat-value text-base">{{ $item['eta'] }}</p>
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
            </x-filament.ui.panel>
        </section>

        <x-filament.ui.panel
            tone="rose"
            eyebrow="Bucket"
            title="Recent Failed Runs"
            description="Diagnosis first: identify whether the failure belongs to content, queue pressure, prompt contract, or the AI runtime."
            :badge="count($failedItems) . ' shown · ' . $recentFailedCount . ' / ' . $windowHours . 'h'"
        >
            @if (count($failedItems) === 0)
                <div class="nova-empty-state">
                    No failed runs in the latest window.
                </div>
            @else
                <div class="grid gap-4 lg:grid-cols-2">
                    @foreach ($failedItems as $item)
                        <article class="rounded-2xl border border-rose-200 bg-rose-50/50 p-4 dark:border-rose-800/40 dark:bg-rose-900/10">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $item['title'] }}</p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">#{{ $item['id'] }} · {{ $item['slug'] }} · {{ $item['updated'] }}</p>
                                </div>
                                <span class="shrink-0 rounded-full bg-rose-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-rose-800 dark:bg-rose-900/30 dark:text-rose-200">failed</span>
                                </div>

                                <div class="mt-4 grid gap-2 text-xs sm:grid-cols-2">
                                    <div class="nova-mini-stat bg-white/95 dark:bg-gray-900/90">
                                        <p class="nova-mini-stat-label">Model</p>
                                        <p class="nova-mini-stat-value text-base">{{ $item['model'] }}</p>
                                    </div>
                                    <div class="nova-mini-stat bg-white/95 dark:bg-gray-900/90">
                                        <p class="nova-mini-stat-label">Latency</p>
                                        <p class="nova-mini-stat-value text-base">{{ $item['latency'] }}</p>
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
        </x-filament.ui.panel>
    </div>
</x-filament-panels::page>
