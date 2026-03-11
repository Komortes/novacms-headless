<x-filament-panels::page>
    <div class="space-y-6">
        <section class="overflow-hidden rounded-3xl border border-amber-200 bg-gradient-to-br from-amber-50 via-orange-50 to-stone-50 shadow-sm dark:border-amber-800/40 dark:from-gray-900 dark:via-gray-900 dark:to-gray-950">
            <div class="grid gap-6 px-6 py-6 lg:grid-cols-[1.6fr_0.9fr] lg:px-8">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-amber-300/60 bg-white/70 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] text-amber-900 dark:border-amber-700/40 dark:bg-white/5 dark:text-amber-100">
                        Editorial Runbook
                    </div>
                    <h2 class="mt-4 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">FAQ and AI Generation Guide</h2>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-gray-700 dark:text-gray-300">
                        Use this page as the operator reference for how summaries are generated, what each state means,
                        and what to do when the flow degrades. It is written for editors first, but it also doubles as a compact backend runbook.
                    </p>

                    <div class="mt-5 flex flex-wrap gap-2">
                        <span class="inline-flex items-center rounded-full bg-white/80 px-3 py-1 text-xs font-semibold text-amber-900 ring-1 ring-amber-300/40 dark:bg-white/5 dark:text-amber-100 dark:ring-white/10">
                            Queue-driven summary pipeline
                        </span>
                        <span class="inline-flex items-center rounded-full bg-white/80 px-3 py-1 text-xs font-semibold text-amber-900 ring-1 ring-amber-300/40 dark:bg-white/5 dark:text-amber-100 dark:ring-white/10">
                            Prompt and model traceability
                        </span>
                    </div>
                </div>

                <aside class="rounded-2xl border border-amber-200/70 bg-white/75 p-5 backdrop-blur dark:border-amber-800/30 dark:bg-white/5">
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
                </aside>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-sky-200 bg-white p-5 shadow-sm dark:border-sky-800/40 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-sky-700 dark:text-sky-300">Generated Output</p>
                <ul class="mt-3 space-y-2 text-sm text-gray-700 dark:text-gray-200">
                    <li>TL;DR</li>
                    <li>Bullets</li>
                    <li>Meta description</li>
                    <li>FAQ entries</li>
                    <li>Tags</li>
                </ul>
            </article>
            <article class="rounded-2xl border border-indigo-200 bg-white p-5 shadow-sm dark:border-indigo-800/40 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700 dark:text-indigo-300">Run Metadata</p>
                <ul class="mt-3 space-y-2 text-sm text-gray-700 dark:text-gray-200">
                    <li>Provider</li>
                    <li>Model</li>
                    <li>Prompt version</li>
                    <li>Latency</li>
                    <li>Token usage</li>
                </ul>
            </article>
            <article class="rounded-2xl border border-amber-200 bg-white p-5 shadow-sm dark:border-amber-800/40 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">Preset Strategy</p>
                <ul class="mt-3 space-y-2 text-sm text-gray-700 dark:text-gray-200">
                    <li><strong>Fast</strong> for draft checks and quick retries</li>
                    <li><strong>Balanced</strong> for day-to-day editing</li>
                    <li><strong>Quality</strong> for higher-confidence final output</li>
                </ul>
            </article>
            <article class="rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm dark:border-emerald-800/40 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Publish Gate</p>
                <ul class="mt-3 space-y-2 text-sm text-gray-700 dark:text-gray-200">
                    <li>Summary status must be <code>ready</code></li>
                    <li>TL;DR must be usable</li>
                    <li>Bullets and tags must be populated</li>
                    <li>At least one FAQ pair is required</li>
                </ul>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
            <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">State Machine</p>
                        <h3 class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">Summary Status Map</h3>
                    </div>
                    <div class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        editor-facing lifecycle
                    </div>
                </div>

                <div class="mt-5 space-y-4">
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800/40 dark:bg-amber-900/10">
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">pending</span>
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Content changed or generation was queued.</p>
                        </div>
                        <p class="mt-2 text-sm leading-6 text-gray-700 dark:text-gray-200">The output is considered stale. This is a waiting state, not a failure.</p>
                    </div>
                    <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4 dark:border-sky-800/40 dark:bg-sky-900/10">
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-sky-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-sky-800 dark:bg-sky-900/40 dark:text-sky-200">generating</span>
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">A worker is processing the request right now.</p>
                        </div>
                        <p class="mt-2 text-sm leading-6 text-gray-700 dark:text-gray-200">Do not queue duplicate runs unless you intentionally want another provider/model attempt.</p>
                    </div>
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800/40 dark:bg-emerald-900/10">
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">ready</span>
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Generated output is available and considered current.</p>
                        </div>
                        <p class="mt-2 text-sm leading-6 text-gray-700 dark:text-gray-200">Review the output and move the content to published only after the quality gate is satisfied.</p>
                    </div>
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-800/40 dark:bg-rose-900/10">
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-rose-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-rose-800 dark:bg-rose-900/40 dark:text-rose-200">failed</span>
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">The worker exhausted the run or the provider returned invalid output.</p>
                        </div>
                        <p class="mt-2 text-sm leading-6 text-gray-700 dark:text-gray-200">Open the record, inspect <code>last_error</code>, and retry only after the underlying issue is clear.</p>
                    </div>
                </div>
            </article>

            <aside class="space-y-6">
                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Retry Playbook</p>
                    <div class="mt-4 space-y-3 text-sm text-gray-700 dark:text-gray-200">
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
                            <p class="mt-1">Retry with another provider/model combination or adjust the prompt version.</p>
                        </div>
                        <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">Timeouts or queue lag</p>
                            <p class="mt-1">Check System Health and Queue Center before triggering more runs.</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">What FAQ Actually Means</p>
                    <p class="mt-4 text-sm leading-6 text-gray-700 dark:text-gray-200">
                        The generated FAQ is not a site-wide FAQ page. It is a per-record structured output derived from that content item,
                        intended for SEO, search previews, or downstream frontend rendering.
                    </p>
                </section>
            </aside>
        </section>

        <section class="grid gap-6 lg:grid-cols-2">
            <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Editorial Flow</p>
                <h3 class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">Recommended Working Sequence</h3>
                <ol class="mt-5 space-y-4 text-sm text-gray-700 dark:text-gray-200">
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
            </article>

            <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Useful Commands</p>
                <h3 class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">CLI Shortcuts For Ops</h3>
                <div class="mt-5 space-y-3 text-sm">
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
            </article>
        </section>
    </div>
</x-filament-panels::page>
