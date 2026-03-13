<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament.ui.hero
            tone="slate"
            eyebrow="Editorial Revision"
            :title="'Edit: ' . $record->title"
            :description="'Slug ' . $record->slug . ' · locale ' . $record->locale . ' · updated ' . $record->updated_at?->diffForHumans()"
        >
            <x-slot:aside>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-300">Current AI State</p>
                <div class="mt-4 space-y-3 text-sm text-slate-200">
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                        <p class="font-semibold text-white">Summary status</p>
                        <p class="mt-1 text-slate-300">{{ $summaryStatus }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                        <p class="font-semibold text-white">Structured output</p>
                        <p class="mt-1 text-slate-300">{{ $bulletCount }} bullets · {{ $faqCount }} FAQ pairs · {{ $tagCount }} tags</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                        <p class="font-semibold text-white">Readiness</p>
                        <p class="mt-1 text-slate-300">{{ $hasReadySummary ? 'Current summary is usable for review.' : 'Summary still needs regeneration or recovery.' }}</p>
                    </div>
                </div>
            </x-slot:aside>

            <div class="mt-5 flex flex-wrap gap-2">
                <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-slate-200 ring-1 ring-white/10">
                    {{ ucfirst($record->status->value) }}
                </span>
                <span class="inline-flex items-center rounded-full bg-sky-400/15 px-3 py-1 text-xs font-medium text-sky-100 ring-1 ring-sky-300/20">
                    AI {{ ucfirst($summaryStatus) }}
                </span>
            </div>
        </x-filament.ui.hero>

        <section class="grid gap-6 xl:grid-cols-[1.6fr_0.8fr]">
            <div>
                {{ $this->content }}
            </div>

            <div class="space-y-6 xl:sticky xl:top-6 xl:self-start">
                <x-filament.ui.panel
                    eyebrow="Editing Discipline"
                    title="When To Regenerate"
                >
                    <div class="space-y-3 text-sm text-gray-700 dark:text-gray-200">
                        <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">Regenerate after semantic changes</p>
                            <p class="mt-1">If the meaning, structure, or target audience changed, the old summary is no longer trustworthy.</p>
                        </div>
                        <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">Do not spam the queue</p>
                            <p class="mt-1">If a run is already generating, wait for the worker unless the current attempt is clearly obsolete.</p>
                        </div>
                    </div>
                </x-filament.ui.panel>

                <x-filament.ui.panel
                    eyebrow="Publish Gate"
                    title="Before Leaving This Form"
                >
                    <ol class="space-y-3 text-sm text-gray-700 dark:text-gray-200">
                        <li class="rounded-2xl border border-gray-200 px-4 py-3 dark:border-gray-700">
                            <span class="font-semibold text-gray-900 dark:text-gray-100">1. Review body changes</span>
                            <p class="mt-1">Confirm the body still reflects the current editorial goal.</p>
                        </li>
                        <li class="rounded-2xl border border-gray-200 px-4 py-3 dark:border-gray-700">
                            <span class="font-semibold text-gray-900 dark:text-gray-100">2. Verify AI freshness</span>
                            <p class="mt-1">Ready summaries are only safe when they match the current source content.</p>
                        </li>
                        <li class="rounded-2xl border border-gray-200 px-4 py-3 dark:border-gray-700">
                            <span class="font-semibold text-gray-900 dark:text-gray-100">3. Publish deliberately</span>
                            <p class="mt-1">Status changes are fast, but they should follow a real review of AI output quality.</p>
                        </li>
                    </ol>
                </x-filament.ui.panel>
            </div>
        </section>
    </div>
</x-filament-panels::page>
