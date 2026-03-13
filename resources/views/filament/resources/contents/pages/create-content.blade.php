<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament.ui.hero
            tone="amber"
            eyebrow="Draft Creation"
            title="Create Content"
            description="Start with a clean editorial draft, define the canonical source, and only then move the record into AI generation."
        >
            <x-slot:aside>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Suggested Sequence</p>
                <ol class="mt-4 space-y-3 text-sm text-gray-700 dark:text-gray-200">
                    @foreach ($recommendedFlow as $step)
                        <li class="rounded-2xl bg-amber-50 px-4 py-3 dark:bg-gray-800/80">{{ $step }}</li>
                    @endforeach
                </ol>
            </x-slot:aside>

            <div class="mt-5 flex flex-wrap gap-2">
                <span class="inline-flex items-center rounded-full bg-white/80 px-3 py-1 text-xs font-semibold text-amber-900 ring-1 ring-amber-300/40 dark:bg-white/5 dark:text-amber-100 dark:ring-white/10">
                    Markdown-first workflow
                </span>
                <span class="inline-flex items-center rounded-full bg-white/80 px-3 py-1 text-xs font-semibold text-amber-900 ring-1 ring-amber-300/40 dark:bg-white/5 dark:text-amber-100 dark:ring-white/10">
                    Async summary pipeline
                </span>
            </div>
        </x-filament.ui.hero>

        <section class="grid gap-6 xl:grid-cols-[1.6fr_0.8fr]">
            <div>
                {{ $this->content }}
            </div>

            <div class="space-y-6 xl:sticky xl:top-6 xl:self-start">
                <x-filament.ui.panel
                    eyebrow="Drafting Tips"
                    title="Write For The Pipeline"
                >
                    <div class="space-y-3 text-sm text-gray-700 dark:text-gray-200">
                        <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">Stable title and slug</p>
                            <p class="mt-1">Treat them as public identifiers. Slug changes later create downstream friction.</p>
                        </div>
                        <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">Meaningful markdown structure</p>
                            <p class="mt-1">Headings and clear sections improve summaries, tags, embeddings, and FAQ extraction.</p>
                        </div>
                    </div>
                </x-filament.ui.panel>

                <x-filament.ui.panel
                    eyebrow="After Save"
                    title="What Happens Next"
                >
                    <ol class="space-y-3 text-sm text-gray-700 dark:text-gray-200">
                        <li class="rounded-2xl border border-gray-200 px-4 py-3 dark:border-gray-700">
                            <span class="font-semibold text-gray-900 dark:text-gray-100">1. Content hash is generated</span>
                            <p class="mt-1">The record becomes traceable for idempotent summary and embedding runs.</p>
                        </li>
                        <li class="rounded-2xl border border-gray-200 px-4 py-3 dark:border-gray-700">
                            <span class="font-semibold text-gray-900 dark:text-gray-100">2. Summary is marked pending</span>
                            <p class="mt-1">Queue generation when the editorial intent is stable enough for AI output.</p>
                        </li>
                    </ol>
                </x-filament.ui.panel>
            </div>
        </section>
    </div>
</x-filament-panels::page>
