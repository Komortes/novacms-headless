<div class="space-y-4">
    <x-filament.ui.hero
        tone="slate"
        eyebrow="Content Workspace"
        title="Editorial Queue-Aware Overview"
        description="Use tabs and filters to narrow the content set, then open a record only when you need deeper review or regeneration."
    >
        <x-slot:aside>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-300">Fast Path</p>
            <ol class="mt-4 space-y-3 text-sm text-slate-200">
                <li class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                    <span class="font-semibold text-white">1. Filter the working set</span>
                    <p class="mt-1 text-slate-300">Use tabs for draft, AI pending, or failed runs before touching row actions.</p>
                </li>
                <li class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                    <span class="font-semibold text-white">2. Read AI status first</span>
                    <p class="mt-1 text-slate-300">The list is optimized to show publishing and AI state together.</p>
                </li>
                <li class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                    <span class="font-semibold text-white">3. Open the record for final review</span>
                    <p class="mt-1 text-slate-300">Use the detailed view only when you need TL;DR, FAQ, timeline, or source markdown.</p>
                </li>
            </ol>
        </x-slot:aside>

        <div class="mt-5 flex flex-wrap gap-2">
            <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-slate-200 ring-1 ring-white/10">
                Total {{ $totalContent }}
            </span>
            <span class="inline-flex items-center rounded-full bg-amber-400/15 px-3 py-1 text-xs font-medium text-amber-100 ring-1 ring-amber-300/20">
                Draft {{ $draftCount }}
            </span>
            <span class="inline-flex items-center rounded-full bg-sky-400/15 px-3 py-1 text-xs font-medium text-sky-100 ring-1 ring-sky-300/20">
                AI in queue {{ $pendingAiCount }}
            </span>
            <span class="inline-flex items-center rounded-full bg-rose-400/15 px-3 py-1 text-xs font-medium text-rose-100 ring-1 ring-rose-300/20">
                AI failed {{ $failedAiCount }}
            </span>
        </div>
    </x-filament.ui.hero>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-filament.ui.metric-card
            label="Total Records"
            :value="$totalContent"
            description="All posts and pages available in the workspace."
            tone="indigo"
        />
        <x-filament.ui.metric-card
            label="Drafts"
            :value="$draftCount"
            description="Editorial items still in preparation."
            tone="amber"
        />
        <x-filament.ui.metric-card
            label="Published"
            :value="$publishedCount"
            description="Content already available for delivery."
            tone="emerald"
        />
        <x-filament.ui.metric-card
            label="AI Attention"
            :value="$pendingAiCount + $failedAiCount"
            description="Queued, generating, or failed runs that still need operator attention."
            tone="rose"
        />
    </section>
</div>
