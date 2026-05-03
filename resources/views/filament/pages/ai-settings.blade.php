<x-filament-panels::page>
    <div class="space-y-5">
        <x-filament.ui.hero
            tone="sky"
            eyebrow="Provider Control"
            title="AI Runtime Baseline"
            description="Define the provider, model, and timeout defaults that shape content generation."
        >
            <div class="flex flex-wrap gap-2">
                <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-slate-200 ring-1 ring-white/10">
                    Default {{ $defaultProvider }}
                </span>
                @if ($runtimeAlertsCount > 0)
                    <span class="inline-flex items-center rounded-full bg-rose-400/15 px-3 py-1 text-xs font-medium text-rose-100 ring-1 ring-rose-300/20">
                        {{ $runtimeAlertsCount }} runtime {{ Str::plural('alert', $runtimeAlertsCount) }}
                    </span>
                @endif
                @if ($storedOpenAiKey)
                    <span class="inline-flex items-center rounded-full bg-emerald-400/15 px-3 py-1 text-xs font-medium text-emerald-100 ring-1 ring-emerald-300/20">
                        OpenAI key stored
                    </span>
                @endif
            </div>
        </x-filament.ui.hero>

        {{-- Three numeric metrics only --}}
        <section class="grid gap-4 sm:grid-cols-3">
            <x-filament.ui.metric-card
                label="Runtime Alerts"
                :value="$runtimeAlertsCount"
                description="Queue lag and failure threshold signals."
                tone="amber"
            />
            <x-filament.ui.metric-card
                label="Warning Checks"
                :value="$warningChecks"
                description="Degraded, not yet failures."
                tone="sky"
            />
            <x-filament.ui.metric-card
                label="Failed Checks"
                :value="$failedChecks"
                description="Blocking failures requiring immediate action."
                tone="rose"
            />
        </section>

        {{-- Provider status cards --}}
        <section class="grid gap-4 xl:grid-cols-2">
            @foreach ($providerCards as $card)
                <x-filament.ui.panel
                    :tone="$card['tone']"
                    :eyebrow="$card['kind']"
                    :title="$card['label']"
                    :badge="$card['status_label']"
                >
                    <p class="text-sm leading-6 text-gray-700 dark:text-gray-200">{{ $card['message'] }}</p>

                    <div class="mt-4 grid grid-cols-3 gap-2 text-xs">
                        <div class="rounded-xl bg-white/80 px-3 py-2 dark:bg-gray-950/60">
                            <p class="font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Base URL</p>
                            <p class="mt-1 break-all text-gray-900 dark:text-gray-100">{{ $card['base_url'] }}</p>
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

        @if (count($profiles) > 0)
            {{-- Generation presets --}}
            <x-filament.ui.panel eyebrow="Presets" title="Generation Profiles">
                <div class="grid gap-3 sm:grid-cols-3">
                    @foreach ($profiles as $profile)
                        <div class="rounded-[1.1rem] border border-slate-200/80 bg-white/80 px-4 py-3 dark:border-slate-800/60 dark:bg-slate-950/55">
                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $profile['label'] }}</p>
                            <div class="mt-2 space-y-1">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Ollama</span>
                                    <span class="text-xs text-slate-700 dark:text-slate-300">{{ $profile['ollama_model'] }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">OpenAI</span>
                                    <span class="text-xs text-slate-700 dark:text-slate-300">{{ $profile['openai_model'] }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament.ui.panel>
        @endif

        {{-- Settings form --}}
        <div>
            {{ $this->content }}
        </div>
    </div>
</x-filament-panels::page>
