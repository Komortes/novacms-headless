<x-filament-panels::page>
    @php
        $statusButtons = [
            'draft' => 'Draft',
            'published' => 'Published',
            'archived' => 'Archived',
        ];
    @endphp

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
                        <p class="mt-1 text-slate-300">{{ ucfirst($summaryStatus) }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                        <p class="font-semibold text-white">Structured output</p>
                        <p class="mt-1 text-slate-300">{{ $bulletCount }} bullets · {{ $faqCount }} FAQ pairs · {{ $tagCount }} tags</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                        <p class="font-semibold text-white">Readiness</p>
                        <p class="mt-1 text-slate-300">{{ $qualityGatePassed ? 'Current summary is review-ready.' : 'Summary still needs regeneration or review.' }}</p>
                    </div>
                </div>
            </x-slot:aside>

            <div class="mt-5 flex flex-wrap gap-2">
                <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-slate-200 ring-1 ring-white/10">
                    {{ ucfirst($draftStatus) }}
                </span>
                <span @class([
                    'inline-flex items-center rounded-full px-3 py-1 text-xs font-medium ring-1',
                    'bg-emerald-400/15 text-emerald-100 ring-emerald-300/20' => $summaryStatus === 'ready',
                    'bg-sky-400/15 text-sky-100 ring-sky-300/20' => $summaryStatus === 'generating',
                    'bg-amber-400/15 text-amber-100 ring-amber-300/20' => $summaryStatus === 'pending',
                    'bg-rose-400/15 text-rose-100 ring-rose-300/20' => $summaryStatus === 'failed',
                ])>
                    AI {{ ucfirst($summaryStatus) }}
                </span>
                <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-slate-200 ring-1 ring-white/10">
                    {{ $readingMinutes }} min read
                </span>
            </div>
        </x-filament.ui.hero>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-filament.ui.metric-card
                label="Draft Words"
                :value="number_format($wordCount)"
                description="Current editor state rendered to markdown."
                tone="indigo"
            />
            <x-filament.ui.metric-card
                label="Bullets"
                :value="$bulletCount"
                description="Latest structured bullet output."
                tone="sky"
            />
            <x-filament.ui.metric-card
                label="FAQ Pairs"
                :value="$faqCount"
                description="Question-answer pairs available now."
                tone="amber"
            />
            <x-filament.ui.metric-card
                label="Tags"
                :value="$tagCount"
                description="Generated tags from the last successful run."
                tone="emerald"
            />
        </section>

        <section
            x-data="{ showRail: true, previewTab: 'rendered' }"
            class="grid gap-6 xl:items-start"
            x-bind:style="window.innerWidth >= 1280 ? `grid-template-columns: ${showRail ? 'minmax(0,1.75fr) minmax(320px,0.95fr)' : 'minmax(0,1fr)'}` : ''"
        >
            <div class="space-y-6">
                <div class="flex justify-end">
                    <button
                        type="button"
                        x-on:click="showRail = !showRail"
                        class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-600 transition hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        <span x-text="showRail ? 'Hide operator rail' : 'Show operator rail'"></span>
                    </button>
                </div>

                <x-filament.ui.panel
                    eyebrow="Draft Editor"
                    title="Canonical Content Source"
                    description="Edit the source markdown here. The preview rail reflects the current draft state."
                >
                    {{ $this->content }}
                </x-filament.ui.panel>

                <x-filament.ui.panel
                    eyebrow="AI Snapshot"
                    title="Latest Generated Output"
                    :badge="$hasReadySummary ? 'ready' : ucfirst($summaryStatus)"
                    :tone="$hasReadySummary ? 'emerald' : ($summaryStatus === 'failed' ? 'rose' : 'amber')"
                >
                    <div class="grid gap-4 lg:grid-cols-[1.15fr_0.85fr]">
                        <div class="rounded-[1.25rem] border border-gray-200 bg-gray-50/80 p-4 dark:border-gray-700 dark:bg-gray-800/80">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">TL;DR</p>
                            <p class="mt-3 text-sm leading-7 text-gray-700 dark:text-gray-200">
                                {{ $record->summary?->summary_tldr ?: 'No summary generated yet.' }}
                            </p>
                        </div>

                        <div class="rounded-[1.25rem] border border-gray-200 bg-gray-50/80 p-4 dark:border-gray-700 dark:bg-gray-800/80">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">Freshness rule</p>
                            <p class="mt-3 text-sm leading-7 text-gray-700 dark:text-gray-200">
                                Regenerate after semantic changes, structure changes, or audience changes. Minor copy edits can wait until the draft stabilizes.
                            </p>
                        </div>
                    </div>
                </x-filament.ui.panel>
            </div>

            <aside
                x-show="showRail"
                x-transition.opacity.duration.200ms
                class="space-y-6 xl:sticky xl:top-6 xl:self-start"
            >
                <x-filament.ui.panel
                    eyebrow="Quick Workflow"
                    title="Status Switch"
                    description="Adjust editorial state without leaving the form."
                    :badge="ucfirst($draftStatus)"
                    :tone="$qualityGatePassed ? 'emerald' : 'amber'"
                >
                    <div class="grid gap-2">
                        @foreach ($statusButtons as $value => $label)
                            <button
                                type="button"
                                wire:click="quickSetStatus('{{ $value }}')"
                                wire:loading.attr="disabled"
                                @class([
                                    'inline-flex items-center justify-center rounded-2xl px-4 py-3 text-sm font-semibold transition',
                                    'bg-gray-900 text-white dark:bg-white dark:text-gray-950' => $draftStatus === $value,
                                    'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' => $draftStatus !== $value,
                                ])
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </x-filament.ui.panel>

                <x-filament.ui.panel
                    eyebrow="Draft Preview"
                    title="Rendered Source"
                    description="Uses the current form state, so you can review the draft without leaving edit mode."
                >
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                x-on:click="previewTab = 'rendered'"
                                x-bind:class="previewTab === 'rendered' ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-950' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300'"
                                class="rounded-full px-3 py-2 text-xs font-semibold transition"
                            >
                                Rendered
                            </button>
                            <button
                                type="button"
                                x-on:click="previewTab = 'raw'"
                                x-bind:class="previewTab === 'raw' ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-950' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300'"
                                class="rounded-full px-3 py-2 text-xs font-semibold transition"
                            >
                                Raw
                            </button>
                        </div>
                        <span class="text-xs uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">{{ $wordCount }} words</span>
                    </div>

                    <div class="mt-4 rounded-[1.25rem] border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-700 dark:bg-gray-800/70">
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $draftTitle }}</p>
                        <p class="mt-1 text-xs uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">{{ $draftSlug }} · {{ strtoupper($draftLocale) }}</p>
                    </div>

                    <div class="mt-4">
                        <div
                            x-show="previewTab === 'rendered'"
                            class="prose prose-gray max-h-[26rem] max-w-none overflow-auto rounded-[1.5rem] border border-gray-200 bg-white px-5 py-4 dark:prose-invert dark:border-gray-700 dark:bg-gray-950/60"
                        >
                            {!! $draftPreviewHtml !!}
                        </div>

                        <pre
                            x-show="previewTab === 'raw'"
                            class="nova-code-block max-h-[26rem] overflow-auto rounded-[1.5rem] border border-gray-200 bg-gray-950/95 p-5 text-sm text-gray-100 dark:border-gray-700"
                        ><code>{{ $draftBody }}</code></pre>
                    </div>
                </x-filament.ui.panel>

                <x-filament.ui.panel
                    eyebrow="Editing Discipline"
                    title="When To Regenerate"
                >
                    <ol class="space-y-3 text-sm text-gray-700 dark:text-gray-200">
                        <li class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <span class="font-semibold text-gray-900 dark:text-gray-100">1. Save structural changes first</span>
                            <p class="mt-1">Headings, sections, and target audience changes should land before queueing a new run.</p>
                        </li>
                        <li class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <span class="font-semibold text-gray-900 dark:text-gray-100">2. Treat ready output as snapshot data</span>
                            <p class="mt-1">A ready summary is only safe if it still reflects the current body and editorial intent.</p>
                        </li>
                        <li class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <span class="font-semibold text-gray-900 dark:text-gray-100">3. Publish only after review</span>
                            <p class="mt-1">The status switch is here for speed, but it should follow a real check of the AI blocks.</p>
                        </li>
                    </ol>
                </x-filament.ui.panel>
            </aside>
        </section>
    </div>
</x-filament-panels::page>
