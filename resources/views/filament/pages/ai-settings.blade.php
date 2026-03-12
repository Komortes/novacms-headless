<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament.ui.hero
            tone="sky"
            eyebrow="Provider Control"
            title="AI Settings"
            description="Define the runtime defaults for local and external providers. Editors can override model and provider per generation run, but this page sets the operational baseline."
        >
            <x-slot:aside>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-300">Operating Model</p>
                <div class="mt-4 space-y-3 text-sm text-slate-200">
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                        <p class="font-semibold text-white">Local first</p>
                        <p class="mt-1 text-slate-300">Use Ollama as the stable default when you want zero-cost local generation.</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                        <p class="font-semibold text-white">External fallback</p>
                        <p class="mt-1 text-slate-300">Keep OpenAI-compatible access ready for stricter output quality or faster remote runs.</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                        <p class="font-semibold text-white">Encrypted secrets</p>
                        <p class="mt-1 text-slate-300">Saved API keys stay in the database encrypted at rest.</p>
                    </div>
                </div>
            </x-slot:aside>

            <div class="mt-5 flex flex-wrap gap-2">
                <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-slate-200 ring-1 ring-white/10">
                    Providers {{ $providerCount }}
                </span>
                <span class="inline-flex items-center rounded-full bg-sky-400/15 px-3 py-1 text-xs font-medium text-sky-100 ring-1 ring-sky-300/20">
                    Ollama models {{ $ollamaModelCount }}
                </span>
                <span class="inline-flex items-center rounded-full bg-indigo-400/15 px-3 py-1 text-xs font-medium text-indigo-100 ring-1 ring-indigo-300/20">
                    OpenAI models {{ $openAiModelCount }}
                </span>
                <span class="inline-flex items-center rounded-full bg-emerald-400/15 px-3 py-1 text-xs font-medium text-emerald-100 ring-1 ring-emerald-300/20">
                    API key {{ $storedOpenAiKey ? 'stored' : 'not configured' }}
                </span>
            </div>
        </x-filament.ui.hero>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-filament.ui.metric-card
                label="Providers"
                :value="$providerCount"
                description="Local and external runtimes available in the admin."
                tone="indigo"
            />
            <x-filament.ui.metric-card
                label="Ollama Models"
                :value="$ollamaModelCount"
                description="Local presets available from the current configuration."
                tone="sky"
            />
            <x-filament.ui.metric-card
                label="OpenAI Models"
                :value="$openAiModelCount"
                description="External models available for remote generation."
                tone="amber"
            />
            <x-filament.ui.metric-card
                label="Secret Status"
                :value="$storedOpenAiKey ? 'saved' : 'missing'"
                description="Encrypted API key state for the external provider."
                tone="emerald"
            />
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.6fr_0.8fr]">
            <div>
                {{ $this->content }}
            </div>

            <div class="space-y-6 xl:sticky xl:top-6 xl:self-start">
                <x-filament.ui.panel
                    eyebrow="Provider Strategy"
                    title="Recommended Defaults"
                >
                    <div class="space-y-3 text-sm text-gray-700 dark:text-gray-200">
                        <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">Fast local baseline</p>
                            <p class="mt-1">Use Ollama with a small Qwen model for local drafts and test runs.</p>
                        </div>
                        <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">Balanced daily setup</p>
                            <p class="mt-1">Keep one dependable local model and one external fallback ready.</p>
                        </div>
                        <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">Quality escalation</p>
                            <p class="mt-1">Reserve remote models for final output checks or difficult content.</p>
                        </div>
                    </div>
                </x-filament.ui.panel>

                <x-filament.ui.panel
                    eyebrow="Safety Notes"
                    title="Before Saving"
                >
                    <ol class="space-y-3 text-sm text-gray-700 dark:text-gray-200">
                        <li class="rounded-2xl border border-gray-200 px-4 py-3 dark:border-gray-700">
                            <span class="font-semibold text-gray-900 dark:text-gray-100">1. Keep timeouts realistic</span>
                            <p class="mt-1">Too-low values create false failures, especially on heavier local models.</p>
                        </li>
                        <li class="rounded-2xl border border-gray-200 px-4 py-3 dark:border-gray-700">
                            <span class="font-semibold text-gray-900 dark:text-gray-100">2. Prefer explicit defaults</span>
                            <p class="mt-1">This page should reflect the runtime you actually expect editors to use.</p>
                        </li>
                        <li class="rounded-2xl border border-gray-200 px-4 py-3 dark:border-gray-700">
                            <span class="font-semibold text-gray-900 dark:text-gray-100">3. Re-test after changes</span>
                            <p class="mt-1">After provider changes, queue one summary generation to validate the new baseline.</p>
                        </li>
                    </ol>
                </x-filament.ui.panel>
            </div>
        </section>
    </div>
</x-filament-panels::page>
