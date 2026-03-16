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
            :eyebrow="strtoupper((string) $record->type->value) . ' Record'"
            :title="$record->title"
            :description="'Slug ' . $record->slug . ' · locale ' . $record->locale . ' · updated ' . $record->updated_at?->diffForHumans()"
        >
            <x-slot:aside>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-300">Operator Notes</p>
                <div class="mt-4 space-y-3 text-sm text-slate-200">
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                        <p class="font-semibold text-white">Next step</p>
                        <p class="mt-1 text-slate-300">{{ $nextStep }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                        <p class="font-semibold text-white">Latest event</p>
                        <p class="mt-1 text-slate-300">{{ $latestEventName }} · {{ $latestEventWhen }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                        <p class="font-semibold text-white">Publish gate</p>
                        <p class="mt-1 text-slate-300">{{ $qualityGatePassed ? 'Structured output is ready for editorial review.' : 'A required summary block is still missing or stale.' }}</p>
                    </div>
                </div>
            </x-slot:aside>

            <div class="mt-5 flex flex-wrap gap-2">
                <span @class([
                    'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1',
                    'bg-emerald-400/15 text-emerald-100 ring-emerald-300/20' => $publicationStatus === 'published',
                    'bg-amber-400/15 text-amber-100 ring-amber-300/20' => $publicationStatus === 'draft',
                    'bg-slate-400/15 text-slate-100 ring-slate-300/20' => $publicationStatus === 'archived',
                ])>
                    {{ ucfirst($publicationStatus) }}
                </span>
                <span @class([
                    'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1',
                    'bg-emerald-400/15 text-emerald-100 ring-emerald-300/20' => $summaryStatus === 'ready',
                    'bg-sky-400/15 text-sky-100 ring-sky-300/20' => $summaryStatus === 'generating',
                    'bg-amber-400/15 text-amber-100 ring-amber-300/20' => $summaryStatus === 'pending',
                    'bg-rose-400/15 text-rose-100 ring-rose-300/20' => $summaryStatus === 'failed',
                ])>
                    AI {{ ucfirst($summaryStatus) }}
                </span>
                <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-slate-200 ring-1 ring-white/10">
                    Locale {{ strtoupper((string) $record->locale) }}
                </span>
                <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-slate-200 ring-1 ring-white/10">
                    {{ $readingMinutes }} min read
                </span>
            </div>
        </x-filament.ui.hero>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-filament.ui.metric-card
                label="Words"
                :value="number_format($wordCount)"
                description="Rendered markdown word count."
                tone="indigo"
            />
            <x-filament.ui.metric-card
                label="Bullets"
                :value="$bulletCount"
                description="Structured bullet points from the latest run."
                tone="sky"
            />
            <x-filament.ui.metric-card
                label="FAQ Pairs"
                :value="$faqCount"
                description="Generated question-answer pairs."
                tone="amber"
            />
            <x-filament.ui.metric-card
                label="Tags"
                :value="$tagCount"
                description="Search and downstream classification tags."
                tone="emerald"
            />
        </section>

        <section
            x-data="{ showRail: true, sourceTab: 'rendered' }"
            class="grid gap-6 xl:items-start"
            x-bind:style="window.innerWidth >= 1280 ? `grid-template-columns: ${showRail ? 'minmax(0,1.7fr) minmax(320px,0.95fr)' : 'minmax(0,1fr)'}` : ''"
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

                <section class="grid gap-6 lg:grid-cols-2">
                    <x-filament.ui.panel
                        eyebrow="Record Overview"
                        title="Content Identity"
                        description="Canonical metadata used by the delivery layer and AI pipeline."
                    >
                        <dl class="grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Type</dt>
                                <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{{ ucfirst((string) $record->type->value) }}</dd>
                            </div>
                            <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</dt>
                                <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{{ ucfirst($publicationStatus) }}</dd>
                            </div>
                            <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Slug</dt>
                                <dd class="mt-1 break-all text-sm font-medium text-gray-900 dark:text-gray-100">{{ $record->slug }}</dd>
                            </div>
                            <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Locale</dt>
                                <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{{ strtoupper((string) $record->locale) }}</dd>
                            </div>
                            <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800 sm:col-span-2">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Content hash</dt>
                                <dd class="mt-1 break-all text-sm font-medium text-gray-900 dark:text-gray-100">{{ $record->content_hash }}</dd>
                            </div>
                        </dl>
                    </x-filament.ui.panel>

                    <x-filament.ui.panel
                        eyebrow="Generation Diagnostics"
                        title="Latest Run Context"
                        :badge="ucfirst($summaryStatus)"
                        :tone="match ($summaryStatus) {
                            'ready' => 'emerald',
                            'generating' => 'sky',
                            'failed' => 'rose',
                            default => 'amber',
                        }"
                    >
                        <dl class="grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Model</dt>
                                <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $summaryModel }}</dd>
                            </div>
                            <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Prompt</dt>
                                <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $summaryPromptVersion }}</dd>
                            </div>
                            <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tokens</dt>
                                <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">in {{ $summaryTokensIn }} / out {{ $summaryTokensOut }}</dd>
                            </div>
                            <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Latency</dt>
                                <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $summaryLatency }}</dd>
                            </div>
                            <div class="rounded-2xl border border-gray-200 px-4 py-3 dark:border-gray-700 sm:col-span-2">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Latest message</dt>
                                <dd class="mt-1 text-sm leading-6 text-gray-700 dark:text-gray-200">{{ $latestEventMessage }}</dd>
                            </div>
                        </dl>
                    </x-filament.ui.panel>
                </section>

                <x-filament.ui.panel
                    eyebrow="AI Output"
                    title="Summary Snapshot"
                    description="Primary generated blocks used for editorial review and downstream delivery."
                >
                    <div class="grid gap-4 lg:grid-cols-[1.2fr_0.8fr]">
                        <div class="space-y-4">
                            <div class="rounded-[1.25rem] border border-gray-200 bg-gray-50/80 p-4 dark:border-gray-700 dark:bg-gray-800/80">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">TL;DR</p>
                                <p class="mt-3 text-sm leading-7 text-gray-700 dark:text-gray-200">
                                    {{ $summaryTldr !== '' ? $summaryTldr : 'No TL;DR generated yet.' }}
                                </p>
                            </div>

                            <div class="rounded-[1.25rem] border border-gray-200 bg-gray-50/80 p-4 dark:border-gray-700 dark:bg-gray-800/80">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">Meta Description</p>
                                <p class="mt-3 text-sm leading-7 text-gray-700 dark:text-gray-200">
                                    {{ $metaDescription !== '' ? $metaDescription : 'No meta description generated yet.' }}
                                </p>
                            </div>
                        </div>

                        <div class="rounded-[1.25rem] border border-gray-200 bg-gray-50/80 p-4 dark:border-gray-700 dark:bg-gray-800/80">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">Tags</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @forelse ($summaryTags as $tag)
                                    <span class="inline-flex items-center rounded-full bg-sky-100 px-3 py-1 text-xs font-medium text-sky-700 dark:bg-sky-900/30 dark:text-sky-200">
                                        {{ $tag }}
                                    </span>
                                @empty
                                    <span class="text-sm text-gray-500 dark:text-gray-400">No tags generated yet.</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </x-filament.ui.panel>

                <section class="grid gap-6 lg:grid-cols-[1.05fr_0.95fr]">
                    <x-filament.ui.panel
                        eyebrow="Structured Points"
                        title="Key Bullets"
                    >
                        <ul class="space-y-2">
                            @forelse ($summaryBullets as $bullet)
                                <li class="flex gap-3 rounded-2xl bg-gray-50 px-4 py-3 text-sm leading-6 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                    <span class="mt-1 h-2 w-2 rounded-full bg-sky-400"></span>
                                    <span>{{ $bullet }}</span>
                                </li>
                            @empty
                                <li class="rounded-2xl bg-gray-50 px-4 py-3 text-sm text-gray-500 dark:bg-gray-800 dark:text-gray-400">No bullet points generated yet.</li>
                            @endforelse
                        </ul>
                    </x-filament.ui.panel>

                    <x-filament.ui.panel
                        eyebrow="Generated FAQ"
                        title="FAQ Snapshot"
                        :badge="$faqCount > 4 ? '+' . ($faqCount - 4) . ' more' : null"
                    >
                        <div class="space-y-3">
                            @forelse (array_slice($summaryFaq, 0, 4) as $item)
                                <div class="rounded-2xl border border-gray-200 px-4 py-3 dark:border-gray-700">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $item['question'] ?? 'Untitled question' }}</p>
                                    <p class="mt-2 text-sm leading-6 text-gray-700 dark:text-gray-200">{{ $item['answer'] ?? 'No answer generated.' }}</p>
                                </div>
                            @empty
                                <div class="rounded-2xl bg-gray-50 px-4 py-3 text-sm text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                    No FAQ pairs generated yet.
                                </div>
                            @endforelse
                        </div>
                    </x-filament.ui.panel>
                </section>

                <x-filament.ui.panel
                    eyebrow="Source Preview"
                    title="Markdown Source"
                    description="Rendered output and raw markdown from the canonical body."
                >
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                x-on:click="sourceTab = 'rendered'"
                                x-bind:class="sourceTab === 'rendered' ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-950' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300'"
                                class="rounded-full px-3 py-2 text-xs font-semibold transition"
                            >
                                Rendered
                            </button>
                            <button
                                type="button"
                                x-on:click="sourceTab = 'raw'"
                                x-bind:class="sourceTab === 'raw' ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-950' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300'"
                                class="rounded-full px-3 py-2 text-xs font-semibold transition"
                            >
                                Raw markdown
                            </button>
                        </div>
                        <p class="text-xs uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">{{ number_format($wordCount) }} words · {{ $readingMinutes }} min read</p>
                    </div>

                    <div class="mt-5">
                        <div
                            x-show="sourceTab === 'rendered'"
                            class="prose prose-gray max-w-none rounded-[1.5rem] border border-gray-200 bg-white px-6 py-5 dark:prose-invert dark:border-gray-700 dark:bg-gray-950/60"
                        >
                            {!! $renderedBody !!}
                        </div>

                        <pre
                            x-show="sourceTab === 'raw'"
                            class="nova-code-block max-h-[34rem] overflow-auto rounded-[1.5rem] border border-gray-200 bg-gray-950/95 p-5 text-sm text-gray-100 dark:border-gray-700"
                        ><code>{{ $record->body }}</code></pre>
                    </div>
                </x-filament.ui.panel>

                <x-filament.ui.panel
                    eyebrow="Run Timeline"
                    title="Recent Summary Events"
                    description="Queue and generation events tied to this record."
                >
                    <div class="space-y-3">
                        @forelse ($recentEvents as $event)
                            <div class="rounded-2xl border border-gray-200 px-4 py-3 dark:border-gray-700">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                            {{ \Illuminate\Support\Str::headline($event['event']) }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $event['when'] }}</span>
                                    </div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $event['provider'] }} · {{ $event['model'] }}</span>
                                </div>
                                <p class="mt-2 text-sm leading-6 text-gray-700 dark:text-gray-200">
                                    {{ $event['message'] !== '' ? $event['message'] : 'No additional event details captured.' }}
                                </p>
                                <div class="mt-3 flex flex-wrap gap-4 text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    <span>Queue wait {{ $event['wait_ms'] }}</span>
                                    <span>Run time {{ $event['duration_ms'] }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-2xl bg-gray-50 px-4 py-3 text-sm text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                No timeline events captured yet.
                            </div>
                        @endforelse
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
                    description="Move the record between editorial states without leaving the page."
                    :badge="ucfirst($publicationStatus)"
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
                                    'bg-gray-900 text-white dark:bg-white dark:text-gray-950' => $publicationStatus === $value,
                                    'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' => $publicationStatus !== $value,
                                ])
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </x-filament.ui.panel>

                <x-filament.ui.panel
                    eyebrow="Quality Gate"
                    title="Publishing Readiness"
                    :badge="$qualityGatePassed ? 'ready to review' : 'needs attention'"
                    :tone="$qualityGatePassed ? 'emerald' : 'amber'"
                >
                    <div class="space-y-3 text-sm text-gray-700 dark:text-gray-200">
                        <div class="rounded-2xl border border-gray-200 px-4 py-3 dark:border-gray-700">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">TL;DR present</p>
                            <p class="mt-1">{{ $summaryTldr !== '' ? 'Yes' : 'Missing' }}</p>
                        </div>
                        <div class="rounded-2xl border border-gray-200 px-4 py-3 dark:border-gray-700">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">Bullets and tags</p>
                            <p class="mt-1">{{ $bulletCount > 0 && $tagCount > 0 ? 'Complete' : 'Incomplete' }}</p>
                        </div>
                        <div class="rounded-2xl border border-gray-200 px-4 py-3 dark:border-gray-700">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">FAQ coverage</p>
                            <p class="mt-1">{{ $faqCount > 0 ? 'At least one pair generated' : 'No FAQ pairs yet' }}</p>
                        </div>
                    </div>
                </x-filament.ui.panel>

                <x-filament.ui.panel
                    eyebrow="Recommended Flow"
                    title="Operator Rail"
                >
                    <ol class="space-y-3 text-sm text-gray-700 dark:text-gray-200">
                        <li class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <span class="font-semibold text-gray-900 dark:text-gray-100">1. Review the summary snapshot</span>
                            <p class="mt-1">Validate TL;DR signal, bullet quality, tags, and FAQ usefulness before touching status.</p>
                        </li>
                        <li class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <span class="font-semibold text-gray-900 dark:text-gray-100">2. Use the status switch deliberately</span>
                            <p class="mt-1">Publishing is fast here, but it should still follow the editorial quality gate.</p>
                        </li>
                        <li class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <span class="font-semibold text-gray-900 dark:text-gray-100">3. Escalate stalled runs</span>
                            <p class="mt-1">If the state remains pending or generating too long, move to Queue Center or System Health.</p>
                        </li>
                    </ol>
                </x-filament.ui.panel>
            </aside>
        </section>
    </div>
</x-filament-panels::page>
