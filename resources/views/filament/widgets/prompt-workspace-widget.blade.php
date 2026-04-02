<div class="space-y-4">
    <x-filament.ui.hero
        tone="sky"
        eyebrow="Prompt Registry"
        title="Prompt Governance Workspace"
        description="Manage prompt families, keep one active production variant per name, and compare versions before changing the live AI summarization contract."
    >
        <x-slot:aside>
            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500">Operator Rules</p>
            <ol class="mt-3 space-y-2 text-sm">
                <li class="rounded-lg border border-white/[0.07] bg-white/[0.04] px-3 py-2.5">
                    <span class="text-xs font-semibold text-slate-200">One active version per family</span>
                    <p class="mt-0.5 text-slate-500">Use active prompt versions as the stable production contract.</p>
                </li>
                <li class="rounded-lg border border-white/[0.07] bg-white/[0.04] px-3 py-2.5">
                    <span class="text-xs font-semibold text-slate-200">Compare before activation</span>
                    <p class="mt-0.5 text-slate-500">Template and parameter diffs should be understood before promoting a new version.</p>
                </li>
            </ol>
        </x-slot:aside>

        <div class="mt-4 flex flex-wrap gap-1.5">
            <span class="inline-flex items-center rounded border border-white/10 bg-white/[0.07] px-2.5 py-0.5 text-[11px] font-medium text-slate-300">
                Total {{ $totalPrompts }}
            </span>
            <span class="inline-flex items-center rounded border border-emerald-700/40 bg-emerald-900/25 px-2.5 py-0.5 text-[11px] font-medium text-emerald-300">
                Active {{ $activePrompts }}
            </span>
            <span class="inline-flex items-center rounded border border-sky-700/40 bg-sky-900/25 px-2.5 py-0.5 text-[11px] font-medium text-sky-300">
                Families {{ $families }}
            </span>
            <span class="inline-flex items-center rounded border border-amber-700/40 bg-amber-900/25 px-2.5 py-0.5 text-[11px] font-medium text-amber-300">
                Inactive {{ $inactivePrompts }}
            </span>
        </div>
    </x-filament.ui.hero>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-filament.ui.metric-card label="Prompt Versions" :value="$totalPrompts"   description="All stored prompt revisions in the registry."                                     tone="indigo" />
        <x-filament.ui.metric-card label="Active"          :value="$activePrompts"  description="Currently live versions used by the AI summarization pipeline."                  tone="emerald" />
        <x-filament.ui.metric-card label="Families"        :value="$families"       description="Distinct prompt names managed in this workspace."                                tone="sky" />
        <x-filament.ui.metric-card label="History"         :value="$inactivePrompts" description="Inactive versions kept for comparison and rollback context."                   tone="amber" />
    </section>

    <section class="grid gap-5 xl:grid-cols-[1.1fr_0.9fr]">
        <x-filament.ui.panel
            eyebrow="Active Families"
            title="Current Registry Surface"
            description="One active version per family should be obvious before you edit or promote a candidate."
        >
            <div class="grid gap-2 md:grid-cols-2">
                @forelse ($featuredFamilies as $family)
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/60">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-slate-950 dark:text-slate-100">{{ $family['name'] }}</p>
                            <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                                {{ $family['active_version'] }}
                            </span>
                        </div>
                        <p class="mt-1.5 font-mono text-[11px] uppercase tracking-[0.12em] text-slate-400 dark:text-slate-500">
                            {{ $family['versions'] }} versions · {{ $family['updated_at'] }}
                        </p>
                    </div>
                @empty
                    <div class="nova-empty-state md:col-span-2">No active prompt families yet.</div>
                @endforelse
            </div>
        </x-filament.ui.panel>

        <x-filament.ui.panel
            eyebrow="Recent Changes"
            title="Latest Registry Activity"
            description="Use this as a quick release log before changing the live AI contract."
        >
            <div class="space-y-2">
                @forelse ($recentChanges as $item)
                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-800/40">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-slate-950 dark:text-slate-100">{{ $item['name'] }}</p>
                            <span @class([
                                'rounded-md px-2 py-0.5 text-[11px] font-semibold',
                                'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' => $item['is_active'],
                                'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'           => ! $item['is_active'],
                            ])>{{ $item['version'] }}</span>
                        </div>
                        <p class="mt-1 font-mono text-[11px] uppercase tracking-[0.12em] text-slate-400 dark:text-slate-500">
                            {{ $item['is_active'] ? 'active' : 'historical' }} · {{ $item['updated_at'] }}
                        </p>
                    </div>
                @empty
                    <div class="nova-empty-state">No prompt activity yet.</div>
                @endforelse
            </div>
        </x-filament.ui.panel>
    </section>
</div>
