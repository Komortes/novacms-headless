<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-lg font-semibold">What this feature does</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                NovaCMS generates structured AI output from each content record: TL;DR, bullets, meta description, FAQ and tags.
                Generation is deterministic by current content state and active prompt version.
            </p>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-base font-semibold">How the pipeline works</h3>
            <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-gray-700 dark:text-gray-200">
                <li>Content is created or edited.</li>
                <li><code>content_hash</code> is recalculated.</li>
                <li>If hash changed, summary status becomes <code>pending</code>.</li>
                <li>When you trigger generation, status changes to <code>generating</code>.</li>
                <li>AI response is parsed and saved into summary fields.</li>
                <li>Status becomes <code>ready</code> or <code>failed</code>.</li>
            </ol>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-base font-semibold">Status meanings</h3>
            <ul class="mt-3 space-y-2 text-sm text-gray-700 dark:text-gray-200">
                <li><strong>pending</strong>: content changed, summary needs generation.</li>
                <li><strong>generating</strong>: generation is in progress.</li>
                <li><strong>ready</strong>: summary is available and up to date.</li>
                <li><strong>failed</strong>: generation failed, check <code>last_error</code> and retry.</li>
            </ul>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-base font-semibold">Operational notes</h3>
            <ul class="mt-3 space-y-2 text-sm text-gray-700 dark:text-gray-200">
                <li>Use <strong>Generate summary</strong> from list/view/edit actions and choose provider/model per run.</li>
                <li>Open <strong>AI Settings</strong> to set defaults and external API credentials.</li>
                <li>Use the <strong>FAQ</strong> block on the content View page to review generated Q&A.</li>
                <li>Change prompt version to control output style without changing application code.</li>
            </ul>
        </section>
    </div>
</x-filament-panels::page>
