<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament.ui.hero
            tone="sky"
            eyebrow="Provider Control"
            title="AI Runtime Baseline"
            description="Define the provider, model, and timeout defaults that shape AI summarization and content generation. This page should describe the runtime the headless CMS actually depends on."
        >
            <x-slot:aside>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-300">Operating Model</p>
                <div class="mt-4 space-y-3 text-sm text-slate-200">
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                        <p class="font-semibold text-white">Local first</p>
                        <p class="mt-1 text-slate-300">Keep Ollama stable for drafts, previews, and low-cost editorial loops.</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                        <p class="font-semibold text-white">Remote fallback</p>
                        <p class="mt-1 text-slate-300">Store an external provider key before quality or latency pressure forces an emergency change.</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                        <p class="font-semibold text-white">Validate after edits</p>
                        <p class="mt-1 text-slate-300">A saved setting is not trustworthy until a real summary run passes on current content.</p>
                    </div>
                </div>
            </x-slot:aside>

            <div class="mt-5 flex flex-wrap gap-2">
                <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-slate-200 ring-1 ring-white/10">
                    Default {{ $defaultProvider }}
                </span>
                <span class="inline-flex items-center rounded-full bg-amber-400/15 px-3 py-1 text-xs font-medium text-amber-100 ring-1 ring-amber-300/20">
                    Runtime alerts {{ $runtimeAlertsCount }}
                </span>
                <span class="inline-flex items-center rounded-full bg-rose-400/15 px-3 py-1 text-xs font-medium text-rose-100 ring-1 ring-rose-300/20">
                    Failed checks {{ $failedChecks }}
                </span>
            </div>
        </x-filament.ui.hero>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <x-filament.ui.metric-card
                label="Providers"
                :value="$providerCount"
                description="Runtimes that can be selected from generation actions."
                tone="indigo"
            />
            <x-filament.ui.metric-card
                label="Default Provider"
                :value="$defaultProvider"
                description="The baseline provider when no override is explicitly chosen."
                tone="sky"
            />
            <x-filament.ui.metric-card
                label="Runtime Alerts"
                :value="$runtimeAlertsCount"
                description="Queue and runtime signals that deserve operator attention."
                tone="amber"
            />
            <x-filament.ui.metric-card
                label="Warning Checks"
                :value="$warningChecks"
                description="Degraded checks that are not yet hard failures."
                tone="gray"
            />
            <x-filament.ui.metric-card
                label="Secret Status"
                :value="$storedOpenAiKey ? 'stored' : 'missing'"
                description="Encrypted API key state for the external fallback."
                tone="emerald"
            />
        </section>

        <section class="grid gap-4 xl:grid-cols-2">
            @foreach ($providerCards as $card)
                <x-filament.ui.panel
                    :tone="$card['tone']"
                    :eyebrow="$card['kind']"
                    :title="$card['label']"
                    :badge="$card['status_label']"
                >
                    <p class="text-sm leading-6 text-gray-700 dark:text-gray-200">{{ $card['message'] }}</p>

                    <div class="mt-4 grid gap-2 text-xs sm:grid-cols-3">
                        <div class="rounded-xl bg-white/80 px-3 py-2 dark:bg-gray-950/60">
                            <p class="font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Base URL</p>
                            <p class="mt-1 break-words text-gray-900 dark:text-gray-100">{{ $card['base_url'] }}</p>
                        </div>
                        <div class="rounded-xl bg-white/80 px-3 py-2 dark:bg-gray-950/60">
                            <p class="font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Model</p>
                            <p class="mt-1 break-words text-gray-900 dark:text-gray-100">{{ $card['model'] }}</p>
                        </div>
                        <div class="rounded-xl bg-white/80 px-3 py-2 dark:bg-gray-950/60">
                            <p class="font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Timeout</p>
                            <p class="mt-1 text-gray-900 dark:text-gray-100">{{ is_numeric($card['timeout']) ? $card['timeout'] . 's' : $card['timeout'] }}</p>
                        </div>
                    </div>
                </x-filament.ui.panel>
            @endforeach
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
            <x-filament.ui.panel
                eyebrow="Runtime Check Feed"
                title="What The Platform Reports Right Now"
                description="Use live checks to separate AI provider misconfiguration from queue or infrastructure pressure."
            >
                @if ($runtimeChecks === [])
                    <div class="rounded-2xl border border-dashed border-gray-300 p-6 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
                        No runtime checks are available yet.
                    </div>
                @else
                    <div class="grid gap-3 md:grid-cols-2">
                        @foreach ($runtimeChecks as $check)
                            <article class="rounded-2xl border border-gray-200 p-4 dark:border-gray-700">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $check['label'] }}</p>
                                        <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $check['message'] }}</p>
                                    </div>
                                    <span @class([
                                        'rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide',
                                        'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200' => $check['tone'] === 'emerald',
                                        'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200' => $check['tone'] === 'amber',
                                        'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-200' => $check['tone'] === 'rose',
                                    ])>
                                        {{ $check['status_label'] }}
                                    </span>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </x-filament.ui.panel>

            <x-filament.ui.panel
                tone="indigo"
                eyebrow="Baseline Rules"
                title="How To Read This Page"
                description="Use the AI baseline as an operating contract, not a place for blind experiments."
            >
                <div class="space-y-3">
                    <div class="nova-signal-card">
                        <p class="nova-signal-card-title">Default route: {{ $defaultProvider }}</p>
                        <p class="nova-signal-card-copy">This is the provider editors will inherit when they do not explicitly override provider or model from generation actions.</p>
                    </div>
                    <div class="nova-signal-card">
                        <p class="nova-signal-card-title">Fallback posture: {{ $storedOpenAiKey ? 'external secret stored' : 'external secret missing' }}</p>
                        <p class="nova-signal-card-copy">Keep the remote fallback ready before local quality, throughput, or latency pressure forces a fast change.</p>
                    </div>
                    <div class="nova-signal-card">
                        <p class="nova-signal-card-title">Operator watch: {{ $failedChecks }} failed / {{ $warningChecks }} warning / {{ $runtimeAlertsCount }} alerts</p>
                        <p class="nova-signal-card-copy">If runtime signals are already degraded, a provider switch alone is unlikely to fix the real bottleneck.</p>
                    </div>
                </div>
            </x-filament.ui.panel>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.55fr_0.85fr]">
            <div>
                {{ $this->content }}
            </div>

            <div class="space-y-6 xl:sticky xl:top-6 xl:self-start">
                <x-filament.ui.panel
                    eyebrow="Profile Matrix"
                    title="Default Escalation Paths"
                    description="Use these as a shared vocabulary for provider and model choices across content and runtime teams."
                >
                    <div class="space-y-3">
                        @foreach ($profiles as $profile)
                            <article class="rounded-2xl border border-gray-200 p-4 dark:border-gray-700">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $profile['label'] }}</p>
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                        {{ $profile['key'] }}
                                    </span>
                                </div>
                                <div class="mt-4 grid gap-2 text-xs sm:grid-cols-2">
                                    <div class="rounded-xl bg-gray-50 px-3 py-2 dark:bg-gray-800">
                                        <p class="font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Ollama</p>
                                        <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $profile['ollama_model'] }}</p>
                                    </div>
                                    <div class="rounded-xl bg-gray-50 px-3 py-2 dark:bg-gray-800">
                                        <p class="font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">OpenAI</p>
                                        <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $profile['openai_model'] }}</p>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </x-filament.ui.panel>

                <x-filament.ui.panel
                    eyebrow="Change Discipline"
                    title="After You Save"
                >
                    <ol class="space-y-3 text-sm text-gray-700 dark:text-gray-200">
                        <li class="rounded-2xl border border-gray-200 px-4 py-3 dark:border-gray-700">
                            <span class="font-semibold text-gray-900 dark:text-gray-100">1. Confirm runtime health</span>
                            <p class="mt-1">If Ollama or Redis is degraded, changing providers will not fix queue pressure by itself.</p>
                        </li>
                        <li class="rounded-2xl border border-gray-200 px-4 py-3 dark:border-gray-700">
                            <span class="font-semibold text-gray-900 dark:text-gray-100">2. Test on one real record</span>
                            <p class="mt-1">Run a fresh summary on current content before treating the new baseline as production-ready.</p>
                        </li>
                        <li class="rounded-2xl border border-gray-200 px-4 py-3 dark:border-gray-700">
                            <span class="font-semibold text-gray-900 dark:text-gray-100">3. Watch queue behavior</span>
                            <p class="mt-1">Look for timeout spikes, failed runs, or throughput regressions after the change lands.</p>
                        </li>
                    </ol>
                </x-filament.ui.panel>

                <x-filament.ui.panel
                    eyebrow="Operational Links"
                    title="Validate The Baseline"
                >
                    <div class="grid gap-3">
                        @foreach ($operatingLinks as $link)
                            <a
                                href="{{ $link['href'] }}"
                                @class([
                                    'group rounded-2xl border px-4 py-4 transition hover:-translate-y-0.5',
                                    'border-indigo-200 bg-indigo-50/70 dark:border-indigo-800/30 dark:bg-indigo-900/10' => $link['tone'] === 'indigo',
                                    'border-amber-200 bg-amber-50/70 dark:border-amber-800/30 dark:bg-amber-900/10' => $link['tone'] === 'amber',
                                    'border-rose-200 bg-rose-50/70 dark:border-rose-800/30 dark:bg-rose-900/10' => $link['tone'] === 'rose',
                                ])
                            >
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $link['label'] }}</p>
                                <p class="mt-2 text-sm leading-6 text-gray-700 dark:text-gray-200">{{ $link['description'] }}</p>
                            </a>
                        @endforeach
                    </div>
                </x-filament.ui.panel>
            </div>
        </section>
    </div>
</x-filament-panels::page>
