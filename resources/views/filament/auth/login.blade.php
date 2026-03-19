<x-filament-panels::page.simple :heading="null" :subheading="null">
    <div class="grid gap-6 lg:grid-cols-[1.05fr_0.95fr]">
        <x-filament.ui.hero
            tone="slate"
            eyebrow="Secure Access"
            title="NovaCMS Admin"
            description="Sign in to the control room for editorial operations, prompt governance, API access, and runtime oversight."
        >
            <x-slot:aside>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-300">Access Rules</p>
                <div class="mt-4 space-y-3 text-sm text-slate-200">
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                        <p class="font-semibold text-white">Use the right role</p>
                        <p class="mt-1 text-slate-300">Admins govern the system, editors review content, operators watch queue and runtime health.</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                        <p class="font-semibold text-white">Treat AI output as operational data</p>
                        <p class="mt-1 text-slate-300">Prompts, providers, queue state, and editorial status all belong to one workflow.</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                        <p class="font-semibold text-white">Enter through the dashboard</p>
                        <p class="mt-1 text-slate-300">The first page after login is the quickest way to decide whether to work in content, queue, or settings.</p>
                    </div>
                </div>
            </x-slot:aside>

            <div class="mt-5 flex flex-wrap gap-2">
                <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-slate-200 ring-1 ring-white/10">
                    Editorial workspace
                </span>
                <span class="inline-flex items-center rounded-full bg-sky-400/15 px-3 py-1 text-xs font-medium text-sky-100 ring-1 ring-sky-300/20">
                    Runtime oversight
                </span>
                <span class="inline-flex items-center rounded-full bg-emerald-400/15 px-3 py-1 text-xs font-medium text-emerald-100 ring-1 ring-emerald-300/20">
                    Prompt governance
                </span>
            </div>
        </x-filament.ui.hero>

        <x-filament.ui.panel
            eyebrow="Authentication"
            :title="$this->getHeading()"
            description="Use your admin credentials to enter the workspace."
        >
            @if (filled($this->getSubheading()))
                <div class="rounded-[1.2rem] border border-slate-200/80 bg-slate-50/80 px-4 py-3 text-sm leading-6 text-slate-600 dark:border-slate-800/70 dark:bg-slate-900/55 dark:text-slate-300">
                    {{ $this->getSubheading() }}
                </div>
            @endif

            <div class="{{ filled($this->getSubheading()) ? 'mt-4' : '' }}">
                {{ $this->content }}
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div class="nova-mini-stat">
                    <p class="nova-mini-stat-label">Workspace</p>
                    <p class="nova-mini-stat-value text-base">Admin panel</p>
                    <p class="nova-mini-stat-description">Content, prompts, tokens, and health live in one control surface.</p>
                </div>
                <div class="nova-mini-stat">
                    <p class="nova-mini-stat-label">Session model</p>
                    <p class="nova-mini-stat-value text-base">Role-aware access</p>
                    <p class="nova-mini-stat-description">What you see next depends on permissions, not a separate app shell.</p>
                </div>
            </div>
        </x-filament.ui.panel>
    </div>
</x-filament-panels::page.simple>
