<div class="space-y-4">
    <x-filament.ui.hero
        tone="sky"
        eyebrow="Prompt Registry"
        title="Versioned Prompt Workspace"
        description="Manage prompt families, keep one active production variant per name, and compare versions before changing the live contract."
    >
        <x-slot:aside>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-300">Operator Rules</p>
            <ol class="mt-4 space-y-3 text-sm text-slate-200">
                <li class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                    <span class="font-semibold text-white">One active version per family</span>
                    <p class="mt-1 text-slate-300">Use active prompt versions as the stable production contract.</p>
                </li>
                <li class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                    <span class="font-semibold text-white">Compare before activation</span>
                    <p class="mt-1 text-slate-300">Template and parameter diffs should be understood before promoting a new version.</p>
                </li>
            </ol>
        </x-slot:aside>

        <div class="mt-5 flex flex-wrap gap-2">
            <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-slate-200 ring-1 ring-white/10">
                Total {{ $totalPrompts }}
            </span>
            <span class="inline-flex items-center rounded-full bg-emerald-400/15 px-3 py-1 text-xs font-medium text-emerald-100 ring-1 ring-emerald-300/20">
                Active {{ $activePrompts }}
            </span>
            <span class="inline-flex items-center rounded-full bg-sky-400/15 px-3 py-1 text-xs font-medium text-sky-100 ring-1 ring-sky-300/20">
                Families {{ $families }}
            </span>
            <span class="inline-flex items-center rounded-full bg-amber-400/15 px-3 py-1 text-xs font-medium text-amber-100 ring-1 ring-amber-300/20">
                Inactive {{ $inactivePrompts }}
            </span>
        </div>
    </x-filament.ui.hero>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-filament.ui.metric-card
            label="Prompt Versions"
            :value="$totalPrompts"
            description="All stored prompt revisions in the registry."
            tone="indigo"
        />
        <x-filament.ui.metric-card
            label="Active"
            :value="$activePrompts"
            description="Currently live versions used by the pipeline."
            tone="emerald"
        />
        <x-filament.ui.metric-card
            label="Families"
            :value="$families"
            description="Distinct prompt names managed in this workspace."
            tone="sky"
        />
        <x-filament.ui.metric-card
            label="History"
            :value="$inactivePrompts"
            description="Inactive versions kept for comparison and rollback context."
            tone="amber"
        />
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        <x-filament.ui.panel
            eyebrow="Active Families"
            title="Current Registry Surface"
            description="One active version per family should be obvious before you edit or promote a candidate."
        >
            <div class="grid gap-3 md:grid-cols-2">
                @forelse ($featuredFamilies as $family)
                    <div class="rounded-[1.25rem] border border-gray-200 bg-gray-50/80 p-4 dark:border-gray-700 dark:bg-gray-800/70">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $family['name'] }}</p>
                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200">
                                {{ $family['active_version'] }}
                            </span>
                        </div>
                        <p class="mt-2 text-xs uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">
                            {{ $family['versions'] }} stored versions · updated {{ $family['updated_at'] }}
                        </p>
                    </div>
                @empty
                    <div class="rounded-[1.25rem] bg-gray-50 px-4 py-4 text-sm text-gray-500 dark:bg-gray-800 dark:text-gray-400 md:col-span-2">
                        No active prompt families yet.
                    </div>
                @endforelse
            </div>
        </x-filament.ui.panel>

        <x-filament.ui.panel
            eyebrow="Recent Changes"
            title="Latest Registry Activity"
            description="Use this as a quick release log before opening compare mode."
        >
            <div class="space-y-3">
                @forelse ($recentChanges as $item)
                    <div class="rounded-2xl border border-gray-200 px-4 py-3 dark:border-gray-700">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $item['name'] }}</p>
                            <span @class([
                                'rounded-full px-2.5 py-1 text-[11px] font-semibold',
                                'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200' => $item['is_active'],
                                'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' => ! $item['is_active'],
                            ])>
                                {{ $item['version'] }}
                            </span>
                        </div>
                        <p class="mt-2 text-xs uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">
                            {{ $item['is_active'] ? 'active version' : 'historical version' }} · updated {{ $item['updated_at'] }}
                        </p>
                    </div>
                @empty
                    <div class="rounded-[1.25rem] bg-gray-50 px-4 py-4 text-sm text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                        No prompt activity yet.
                    </div>
                @endforelse
            </div>
        </x-filament.ui.panel>
    </section>
</div>
