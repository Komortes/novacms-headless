<x-filament-panels::page.simple :heading="null" :subheading="null">
    <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
        {{-- Left: Brand + system context --}}
        <div class="relative overflow-hidden rounded-2xl border border-white/[0.07] bg-[#0a0f1e] px-7 py-8"
             style="background-image: linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px); background-size: 48px 48px;">
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-white/[0.03] via-transparent to-black/20"></div>
            <div class="relative z-10">
                {{-- Brand --}}
                <div class="inline-flex items-center gap-2 rounded border border-white/10 bg-white/[0.07] px-2.5 py-1">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                    <span class="text-[10px] font-semibold uppercase tracking-[0.22em] text-slate-300">NovaCMS</span>
                </div>

                <h1 class="mt-4 text-2xl font-semibold tracking-tight text-white">Admin Control Surface</h1>
                <p class="mt-2 max-w-sm text-sm leading-6 text-slate-400">
                    Headless content operations, AI summarization pipeline, prompt governance, and runtime health — in one role-aware panel.
                </p>

                {{-- System areas --}}
                <div class="mt-7 space-y-2">
                    @foreach ([
                        ['label' => 'Content workspace',    'desc' => 'Draft, review, publish, deliver'],
                        ['label' => 'AI summarization',      'desc' => 'Queue, generate, inspect, retry'],
                        ['label' => 'Prompt governance',     'desc' => 'Contracts, versioning, activation'],
                        ['label' => 'Runtime oversight',     'desc' => 'Queue health, alerts, system status'],
                    ] as $area)
                        <div class="flex items-center gap-3 rounded-xl border border-white/[0.07] bg-white/[0.04] px-4 py-3">
                            <div class="h-1 w-4 rounded-full bg-amber-400/60"></div>
                            <div>
                                <p class="text-sm font-semibold text-slate-200">{{ $area['label'] }}</p>
                                <p class="text-xs text-slate-500">{{ $area['desc'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Footer note --}}
                <p class="mt-6 text-xs text-slate-600">Access level determined by role assignment — use the account matching your operational responsibility.</p>
            </div>
        </div>

        {{-- Right: Auth form --}}
        <div class="rounded-2xl border border-slate-200 bg-white px-6 py-7 shadow-sm dark:border-slate-800 dark:bg-slate-900"
             style="border-top: 2px solid #f59e0b;">
            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Authentication</p>
            <h2 class="mt-1 text-base font-semibold tracking-tight text-slate-950 dark:text-slate-100">
                {{ $this->getHeading() ?? 'Sign in' }}
            </h2>
            <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">Use your admin credentials to enter the workspace.</p>

            @if (filled($this->getSubheading()))
                <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-600 dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-300">
                    {{ $this->getSubheading() }}
                </div>
            @endif

            <div class="mt-5">
                {{ $this->content }}
            </div>
        </div>
    </div>
</x-filament-panels::page.simple>
