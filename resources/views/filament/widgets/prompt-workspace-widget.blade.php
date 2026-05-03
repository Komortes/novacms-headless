<div class="space-y-4">
    <x-filament.ui.hero
        tone="sky"
        eyebrow="Prompt Registry"
        title="Versioned Prompt Workspace"
        description="Manage prompt families, keep one active production variant per name, and compare versions before changing the live contract."
    />

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-filament.ui.metric-card
            label="Prompt Versions"
            :value="$totalPrompts"
            description="All stored prompt revisions."
            tone="indigo"
        />
        <x-filament.ui.metric-card
            label="Active"
            :value="$activePrompts"
            description="Currently live versions."
            tone="emerald"
        />
        <x-filament.ui.metric-card
            label="Families"
            :value="$families"
            description="Distinct prompt names."
            tone="sky"
        />
        <x-filament.ui.metric-card
            label="History"
            :value="$inactivePrompts"
            description="Inactive for comparison."
            tone="amber"
        />
    </section>

    <section class="grid gap-5 xl:grid-cols-[1.1fr_0.9fr]">
        <x-filament.ui.panel
            eyebrow="Active Families"
            title="Current Registry Surface"
            description="One active version per family before you edit or promote a candidate."
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
                            {{ $family['versions'] }} versions · {{ $family['updated_at'] }}
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
                            {{ $item['is_active'] ? 'active' : 'historical' }} · {{ $item['updated_at'] }}
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
