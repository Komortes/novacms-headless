<x-filament-panels::page.simple :heading="null" :subheading="null">
    <div class="mx-auto w-full max-w-sm space-y-5">
        <div class="space-y-1">
            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">NovaCMS</p>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">{{ $this->getHeading() }}</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Editorial workspace · AI runtime · Prompt governance</p>
        </div>

        <div class="rounded-[1.5rem] border border-slate-200/80 bg-white p-5 shadow-sm dark:border-slate-800/70 dark:bg-slate-950">
            @if (filled($this->getSubheading()))
                <div class="mb-4 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600 dark:bg-slate-900 dark:text-slate-300">
                    {{ $this->getSubheading() }}
                </div>
            @endif
            {{ $this->content }}
        </div>

        <div class="flex flex-wrap justify-center gap-2">
            <span class="nova-pill">Role-aware access</span>
            <span class="nova-pill nova-pill-sky">Runtime oversight</span>
            <span class="nova-pill nova-pill-emerald">Prompt registry</span>
        </div>
    </div>
</x-filament-panels::page.simple>
