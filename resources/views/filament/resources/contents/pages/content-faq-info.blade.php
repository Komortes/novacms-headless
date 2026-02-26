<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-2xl border border-amber-200 bg-gradient-to-r from-amber-50 to-orange-50 p-6 shadow-sm dark:border-amber-800/50 dark:from-gray-900 dark:to-gray-900">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">FAQ and AI Generation Guide</h2>
            <p class="mt-2 max-w-4xl text-sm text-gray-700 dark:text-gray-300">
                Operational quick-reference for editors and developers.
            </p>
        </section>

        <section class="grid gap-4 lg:grid-cols-3">
            <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Start in 3 steps</p>
                <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-gray-700 dark:text-gray-200">
                    <li>Open content record.</li>
                    <li>Pick preset + provider in <strong>Generate summary</strong>.</li>
                    <li>Wait for <code>ready</code> and review FAQ block.</li>
                </ol>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Run metadata</p>
                <ul class="mt-3 space-y-2 text-sm text-gray-700 dark:text-gray-200">
                    <li>Prompt version</li>
                    <li>Model id</li>
                    <li>Tokens and latency</li>
                </ul>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Preset usage</p>
                <ul class="mt-3 space-y-2 text-sm text-gray-700 dark:text-gray-200">
                    <li>Use <strong>Fast</strong> for drafts.</li>
                    <li>Use <strong>Quality</strong> for final publish.</li>
                    <li>Check <code>last_error</code> before retry loop.</li>
                </ul>
            </article>
        </section>

        <section class="grid gap-6 lg:grid-cols-2">
            <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Status map</h3>
                <div class="mt-3 space-y-3 text-sm text-gray-700 dark:text-gray-200">
                    <div><span class="inline-flex rounded bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">pending</span> <span class="ml-2">Summary needs regeneration.</span></div>
                    <div><span class="inline-flex rounded bg-sky-100 px-2 py-0.5 text-xs font-semibold text-sky-800 dark:bg-sky-900/40 dark:text-sky-200">generating</span> <span class="ml-2">Queue worker is processing request.</span></div>
                    <div><span class="inline-flex rounded bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">ready</span> <span class="ml-2">Output is available and current.</span></div>
                    <div><span class="inline-flex rounded bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-800 dark:bg-rose-900/40 dark:text-rose-200">failed</span> <span class="ml-2">Check <code>last_error</code>, then retry.</span></div>
                </div>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Troubleshooting</h3>
                <div class="mt-3 space-y-2 text-sm">
                    <details class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <summary class="cursor-pointer font-medium text-gray-800 dark:text-gray-100">Model not found</summary>
                        <p class="mt-2 text-gray-600 dark:text-gray-300"><code>docker compose exec ollama ollama pull &lt;model&gt;</code></p>
                    </details>
                    <details class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <summary class="cursor-pointer font-medium text-gray-800 dark:text-gray-100">Out of memory</summary>
                        <p class="mt-2 text-gray-600 dark:text-gray-300">Switch to a lighter model (for example <code>qwen2.5:1.5b</code>).</p>
                    </details>
                    <details class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <summary class="cursor-pointer font-medium text-gray-800 dark:text-gray-100">Invalid JSON output</summary>
                        <p class="mt-2 text-gray-600 dark:text-gray-300">Retry with another model preset or adjust prompt version.</p>
                    </details>
                    <details class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <summary class="cursor-pointer font-medium text-gray-800 dark:text-gray-100">Timeouts</summary>
                        <p class="mt-2 text-gray-600 dark:text-gray-300">Increase provider timeout from AI settings.</p>
                    </details>
                </div>
            </article>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Minimal pipeline</h3>
            <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-gray-700 dark:text-gray-200">
                <li>Save content -> hash updated.</li>
                <li>Queue generation from actions.</li>
                <li>Wait for <code>ready</code> and publish.</li>
            </ol>
        </section>
    </div>
</x-filament-panels::page>
