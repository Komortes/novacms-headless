<div class="flex flex-wrap items-center gap-x-5 gap-y-1.5 rounded-xl border border-slate-200/70 bg-slate-50/80 px-4 py-2.5 dark:border-slate-800/60 dark:bg-slate-900/50">
    <span class="text-xs text-slate-500 dark:text-slate-400">
        <span class="font-semibold tabular-nums text-slate-800 dark:text-slate-200">{{ $totalContent }}</span> records
    </span>
    <span class="text-xs text-amber-600 dark:text-amber-400">
        <span class="font-semibold tabular-nums">{{ $draftCount }}</span> draft
    </span>
    <span class="text-xs text-emerald-600 dark:text-emerald-400">
        <span class="font-semibold tabular-nums">{{ $publishedCount }}</span> published
    </span>
    @php $aiAttention = $pendingAiCount + $generatingAiCount + $failedAiCount; @endphp
    @if ($aiAttention > 0)
        <span class="text-xs text-rose-600 dark:text-rose-400">
            <span class="font-semibold tabular-nums">{{ $aiAttention }}</span> AI attention
        </span>
    @endif
    @if ($readyAiCount > 0)
        <span class="text-xs text-sky-600 dark:text-sky-400">
            <span class="font-semibold tabular-nums">{{ $readyAiCount }}</span> ready
        </span>
    @endif
</div>
