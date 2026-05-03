<div class="nova-workspace space-y-4">

    {{-- Status header: title + inline key numbers --}}
    <header class="flex flex-wrap items-center justify-between gap-3 rounded-[1.25rem] border border-slate-200/70 bg-white px-5 py-4 shadow-sm dark:border-white/[0.07] dark:bg-slate-950">
        <div class="flex min-w-0 items-center gap-4">
            <div class="min-w-0">
                <p class="nova-kicker">Operations</p>
                <h2 class="mt-0.5 text-xl font-semibold tracking-tight text-slate-950 dark:text-white">NovaCMS Admin</h2>
            </div>
            <span class="hidden shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 dark:bg-white/10 dark:text-slate-300 sm:inline">
                {{ $roleLabel }}
            </span>
        </div>

        <div class="flex items-stretch divide-x divide-slate-200 dark:divide-white/10">
            <div class="pr-5 text-right">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Content</p>
                <p class="mt-0.5 text-xl font-semibold leading-none tabular-nums text-slate-950 dark:text-white">{{ $totalContent }}</p>
            </div>
            <div class="px-5 text-right">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Queue</p>
                <p class="mt-0.5 text-xl font-semibold leading-none tabular-nums {{ $pendingSummaries > 0 ? 'text-sky-600 dark:text-sky-400' : 'text-slate-950 dark:text-white' }}">{{ $pendingSummaries }}</p>
            </div>
            <div class="pl-5 text-right">
                <p class="text-[10px] font-semibold uppercase tracking-wide {{ $alertsCount > 0 ? 'text-rose-400' : 'text-slate-400' }}">Alerts</p>
                <p class="mt-0.5 text-xl font-semibold leading-none tabular-nums {{ $alertsCount > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                    {{ $alertsCount > 0 ? $alertsCount : '—' }}
                </p>
            </div>
        </div>
    </header>

    {{-- Main: recent activity (dominant) + right column --}}
    <section class="grid gap-4 xl:grid-cols-[1.5fr_1fr]">

        <x-filament.ui.panel eyebrow="Recent Content" title="Latest Activity" padding="p-4">
            <div class="divide-y divide-slate-200/70 dark:divide-white/10">
                @forelse ($recentContent as $item)
                    <a href="{{ $item['href'] }}" class="nova-row-link">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $item['title'] }}</p>
                            <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">{{ $item['slug'] }} · {{ $item['updated_at'] }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-1.5">
                            <span class="nova-count-badge">{{ ucfirst($item['status']) }}</span>
                            <span @class([
                                'nova-count-badge',
                                'nova-count-emerald' => $item['summary_status'] === 'ready',
                                'nova-count-sky'     => $item['summary_status'] === 'generating',
                                'nova-count-amber'   => $item['summary_status'] === 'pending',
                                'nova-count-rose'    => $item['summary_status'] === 'failed',
                            ])>{{ $item['summary_status'] }}</span>
                        </div>
                    </a>
                @empty
                    <div class="nova-empty-state">No recent content activity yet.</div>
                @endforelse
            </div>
        </x-filament.ui.panel>

        <div class="space-y-4">

            {{-- Triage --}}
            <x-filament.ui.panel eyebrow="Triage" title="Needs Attention" padding="p-4">
                @if (count($attentionItems) === 0)
                    <div class="nova-empty-state">Nothing flagged right now.</div>
                @else
                    <div class="space-y-2">
                        @foreach ($attentionItems as $item)
                            <a href="{{ $item['href'] }}" class="nova-link-tile nova-link-tile-compact">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $item['title'] }}</p>
                                        <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">{{ $item['description'] }}</p>
                                    </div>
                                    <span @class([
                                        'nova-count-badge shrink-0',
                                        'nova-count-indigo'  => $item['tone'] === 'indigo',
                                        'nova-count-amber'   => $item['tone'] === 'amber',
                                        'nova-count-rose'    => $item['tone'] === 'rose',
                                        'nova-count-sky'     => $item['tone'] === 'sky',
                                        'nova-count-emerald' => $item['tone'] === 'emerald',
                                    ])>act</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </x-filament.ui.panel>

            {{-- Runtime alerts --}}
            <x-filament.ui.panel
                tone="{{ $alertsCount > 0 ? 'rose' : 'emerald' }}"
                eyebrow="Runtime"
                title="Current Risk"
                :badge="$alertsCount > 0 ? $alertsCount . ' active' : 'stable'"
                padding="p-4"
            >
                @forelse ($alerts as $alert)
                    <div class="{{ $loop->first ? '' : 'mt-2 ' }}nova-signal-card nova-signal-card-compact">
                        <div class="flex items-center justify-between gap-3">
                            <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $alert['title'] }}</p>
                            <span class="nova-count-badge nova-count-rose shrink-0">{{ $alert['severity'] }}</span>
                        </div>
                        <p class="mt-1 line-clamp-2 text-xs leading-5 text-slate-600 dark:text-slate-300">{{ $alert['message'] }}</p>
                    </div>
                @empty
                    <div class="rounded-xl bg-emerald-50 px-3 py-2.5 text-sm text-emerald-800 dark:bg-emerald-900/15 dark:text-emerald-100">
                        No queue-health alerts active.
                    </div>
                @endforelse
            </x-filament.ui.panel>

            {{-- AI generation feed --}}
            <x-filament.ui.panel tone="sky" eyebrow="AI Events" title="Generation Feed" padding="p-4">
                <div class="space-y-2">
                    @forelse ($recentEvents as $event)
                        <div class="nova-signal-card nova-signal-card-compact">
                            <div class="flex items-center justify-between gap-3">
                                <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $event['title'] }}</p>
                                @if ($event['href'])
                                    <a href="{{ $event['href'] }}" class="nova-count-badge nova-count-sky shrink-0">open</a>
                                @endif
                            </div>
                            <p class="mt-0.5 truncate text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                {{ \Illuminate\Support\Str::headline($event['event']) }} · {{ $event['provider'] }} · {{ $event['updated_at'] }}
                            </p>
                        </div>
                    @empty
                        <div class="nova-empty-state">No recent AI events yet.</div>
                    @endforelse
                </div>
            </x-filament.ui.panel>

        </div>
    </section>

</div>
