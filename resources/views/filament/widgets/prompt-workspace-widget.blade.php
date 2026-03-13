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
</div>
