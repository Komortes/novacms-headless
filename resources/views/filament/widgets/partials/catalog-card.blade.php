<div @class([
    'relative flex flex-col gap-2.5 rounded-xl border bg-white/80 px-3.5 py-3 ring-1 dark:bg-slate-950/55',
    'border-slate-200/80 dark:border-slate-800/60',
    // Tone-coloured ring
    'ring-sky-200 dark:ring-sky-800/50'     => ($entry['tone'] ?? '') === 'sky',
    'ring-indigo-200 dark:ring-indigo-800/50' => ($entry['tone'] ?? '') === 'indigo',
    'ring-amber-200 dark:ring-amber-800/50'  => ($entry['tone'] ?? '') === 'amber',
    'ring-emerald-200 dark:ring-emerald-800/50' => ($entry['tone'] ?? '') === 'emerald',
])>
    {{-- Header row --}}
    <div class="flex items-start justify-between gap-2">
        <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">
                {{ $entry['name'] }}
            </p>
            <p class="mt-0.5 text-xs leading-4 text-slate-500 dark:text-slate-400">
                {{ $entry['description'] }}
            </p>
        </div>

        {{-- Status badge --}}
        @if ($entry['installed'])
            <span class="shrink-0 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">
                Installed
            </span>
        @elseif ($entry['pulling'])
            <span class="shrink-0 rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-sky-700 dark:bg-sky-900/40 dark:text-sky-300">
                Pulling…
            </span>
        @endif
    </div>

    {{-- Meta chips --}}
    <div class="flex flex-wrap gap-1.5">
        {{-- Tone-coloured param chip --}}
        <span @class([
            'rounded-md px-1.5 py-0.5 text-[10px] font-medium',
            'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300'       => ($entry['tone'] ?? '') === 'sky',
            'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' => ($entry['tone'] ?? '') === 'indigo',
            'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'   => ($entry['tone'] ?? '') === 'amber',
            'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' => ($entry['tone'] ?? '') === 'emerald',
        ])>{{ $entry['param_size'] }}</span>

        <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
            {{ $entry['size_approx'] }}
        </span>
    </div>

    {{-- Action --}}
    @if ($entry['installed'])
        <p class="text-[11px] text-emerald-700 dark:text-emerald-400">Ready to use</p>
    @elseif ($entry['pulling'])
        <p class="text-[11px] text-sky-600 dark:text-sky-400">Pull in progress…</p>
    @else
        <button
            wire:click="pullFromCatalog('{{ $entry['id'] }}')"
            class="mt-auto inline-flex items-center gap-1.5 self-start rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
        >
            <x-heroicon-m-arrow-down-tray class="size-3.5" />
            Pull
        </button>
    @endif
</div>
