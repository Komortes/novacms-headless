<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament.ui.hero
            tone="amber"
            eyebrow="Editorial Runbook"
            title="FAQ and AI Generation Guide"
            description="Use this page as the operator reference for how summaries are generated, what each state means, and what to do when the flow degrades."
        >
            <x-slot:aside>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Start Here</p>
                <ol class="mt-4 space-y-3 text-sm text-gray-700 dark:text-gray-200">
                    <li class="rounded-2xl bg-amber-50 px-4 py-3 dark:bg-gray-800/80">
                        <span class="font-semibold text-gray-900 dark:text-gray-100">1. Edit content</span>
                        <p class="mt-1">Save markdown changes and let the content hash mark AI output as stale.</p>
                    </li>
                    <li class="rounded-2xl bg-amber-50 px-4 py-3 dark:bg-gray-800/80">
                        <span class="font-semibold text-gray-900 dark:text-gray-100">2. Queue generation</span>
                        <p class="mt-1">Choose provider and preset, then queue a summary run from the record or list view.</p>
                    </li>
                    <li class="rounded-2xl bg-amber-50 px-4 py-3 dark:bg-gray-800/80">
                        <span class="font-semibold text-gray-900 dark:text-gray-100">3. Review before publish</span>
                        <p class="mt-1">Wait for <code>ready</code>, inspect TL;DR, bullets, tags, and FAQ, then publish.</p>
                    </li>
                </ol>
            </x-slot:aside>

            <div class="mt-5 flex flex-wrap gap-2">
                <span class="inline-flex items-center rounded-full bg-white/80 px-3 py-1 text-xs font-semibold text-amber-900 ring-1 ring-amber-300/40 dark:bg-white/5 dark:text-amber-100 dark:ring-white/10">
                    Queue-driven summary pipeline
                </span>
                <span class="inline-flex items-center rounded-full bg-white/80 px-3 py-1 text-xs font-semibold text-amber-900 ring-1 ring-amber-300/40 dark:bg-white/5 dark:text-amber-100 dark:ring-white/10">
                    Prompt and model traceability
                </span>
            </div>
        </x-filament.ui.hero>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-filament.ui.metric-card
                label="Generated Outputs"
                value="5"
                description="TL;DR, bullets, meta description, FAQ, and tags."
                tone="sky"
            />
            <x-filament.ui.metric-card
                label="Tracked Metadata"
                value="5"
                description="Provider, model, prompt version, latency, and token usage."
                tone="indigo"
            />
            <x-filament.ui.metric-card
                label="Preset Modes"
                value="3"
                description="Fast for drafts, balanced for daily work, quality for near-final output."
                tone="amber"
            />
            <x-filament.ui.metric-card
                label="Publish Checks"
                value="4"
                description="Ready summary, valid TL;DR, populated bullets and tags, at least one FAQ pair."
                tone="emerald"
            />
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
            <x-filament.ui.panel
                eyebrow="State Machine"
                title="Summary Status Map"
                badge="editor-facing lifecycle"
            >
                <div class="space-y-4">
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800/40 dark:bg-amber-900/10">
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">pending</span>
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Content changed or generation was queued.</p>
                        </div>
                        <p class="mt-2 text-sm leading-6 text-gray-700 dark:text-gray-200">The output is stale. This is a waiting state, not a failure.</p>
                    </div>
                    <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4 dark:border-sky-800/40 dark:bg-sky-900/10">
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-sky-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-sky-800 dark:bg-sky-900/40 dark:text-sky-200">generating</span>
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">A worker is processing the request right now.</p>
                        </div>
                        <p class="mt-2 text-sm leading-6 text-gray-700 dark:text-gray-200">Do not queue duplicates unless you intentionally want another provider or model attempt.</p>
                    </div>
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800/40 dark:bg-emerald-900/10">
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">ready</span>
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Generated output is available and considered current.</p>
                        </div>
                        <p class="mt-2 text-sm leading-6 text-gray-700 dark:text-gray-200">Review the output and publish only after the quality gate is satisfied.</p>
                    </div>
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-800/40 dark:bg-rose-900/10">
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-rose-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-rose-800 dark:bg-rose-900/40 dark:text-rose-200">failed</span>
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">The worker exhausted the run or the provider returned invalid output.</p>
                        </div>
                        <p class="mt-2 text-sm leading-6 text-gray-700 dark:text-gray-200">Open the record, inspect <code>last_error</code>, and retry only after the underlying issue is clear.</p>
                    </div>
                </div>
            </x-filament.ui.panel>

            <div class="space-y-6">
                <x-filament.ui.panel
                    eyebrow="Retry Playbook"
                    title="Common Recovery Paths"
                >
                    <div class="space-y-3 text-sm text-gray-700 dark:text-gray-200">
                        <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">Model not found</p>
                            <p class="mt-1"><code>docker compose exec ollama ollama pull &lt;model&gt;</code></p>
                        </div>
                        <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">Local memory pressure</p>
                            <p class="mt-1">Switch to a smaller preset or model before retrying.</p>
                        </div>
                        <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">Invalid JSON output</p>
                            <p class="mt-1">Retry with another provider or model combination, or adjust the prompt version.</p>
                        </div>
                        <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">Timeouts or queue lag</p>
                            <p class="mt-1">Check System Health and Queue Center before triggering more runs.</p>
                        </div>
                    </div>
                </x-filament.ui.panel>

                <x-filament.ui.panel
                    eyebrow="FAQ Semantics"
                    title="What FAQ Actually Means"
                >
                    <p class="text-sm leading-6 text-gray-700 dark:text-gray-200">
                        The generated FAQ is not a site-wide FAQ page. It is a per-record structured output derived from that content item, intended for SEO, search previews, or downstream frontend rendering.
                    </p>
                </x-filament.ui.panel>
            </div>
        </section>

        <section class="grid gap-6 lg:grid-cols-2">
            <x-filament.ui.panel
                eyebrow="Editorial Flow"
                title="Recommended Working Sequence"
            >
                <ol class="space-y-4 text-sm text-gray-700 dark:text-gray-200">
                    <li class="rounded-2xl border border-gray-200 px-4 py-4 dark:border-gray-700">
                        <span class="font-semibold text-gray-900 dark:text-gray-100">Draft the content</span>
                        <p class="mt-1">Write or update the markdown body until the editorial intent is stable.</p>
                    </li>
                    <li class="rounded-2xl border border-gray-200 px-4 py-4 dark:border-gray-700">
                        <span class="font-semibold text-gray-900 dark:text-gray-100">Queue summary generation</span>
                        <p class="mt-1">Use a balanced preset first. Move to quality only when the record is near final.</p>
                    </li>
                    <li class="rounded-2xl border border-gray-200 px-4 py-4 dark:border-gray-700">
                        <span class="font-semibold text-gray-900 dark:text-gray-100">Review structured output</span>
                        <p class="mt-1">Check TL;DR accuracy, bullet signal, meta description length, and FAQ usefulness.</p>
                    </li>
                    <li class="rounded-2xl border border-gray-200 px-4 py-4 dark:border-gray-700">
                        <span class="font-semibold text-gray-900 dark:text-gray-100">Publish when gate passes</span>
                        <p class="mt-1">The publishing gate protects against incomplete or obviously weak AI output.</p>
                    </li>
                </ol>
            </x-filament.ui.panel>

            <x-filament.ui.panel
                eyebrow="Useful Commands"
                title="CLI Shortcuts For Ops"
            >
                <div class="space-y-3 text-sm">
                    <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                        <p class="font-semibold text-gray-900 dark:text-gray-100">Import demo content</p>
                        <p class="mt-2 overflow-auto rounded-xl bg-gray-900 px-3 py-2 font-mono text-xs text-gray-100">php artisan content:import --demo</p>
                    </div>
                    <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                        <p class="font-semibold text-gray-900 dark:text-gray-100">Queue a summary for one record</p>
                        <p class="mt-2 overflow-auto rounded-xl bg-gray-900 px-3 py-2 font-mono text-xs text-gray-100">php artisan content:generate-summary sample-post</p>
                    </div>
                    <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                        <p class="font-semibold text-gray-900 dark:text-gray-100">Rebuild embeddings</p>
                        <p class="mt-2 overflow-auto rounded-xl bg-gray-900 px-3 py-2 font-mono text-xs text-gray-100">php artisan content:reindex-embeddings --mode=full</p>
                    </div>
                    <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                        <p class="font-semibold text-gray-900 dark:text-gray-100">Run health checks</p>
                        <p class="mt-2 overflow-auto rounded-xl bg-gray-900 px-3 py-2 font-mono text-xs text-gray-100">php artisan stack:smoke</p>
                    </div>
                </div>
            </x-filament.ui.panel>
        </section>
    </div>
</x-filament-panels::page>
