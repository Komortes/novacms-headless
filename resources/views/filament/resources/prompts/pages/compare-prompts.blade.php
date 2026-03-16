<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament.ui.hero
            tone="sky"
            eyebrow="Prompt Diff"
            title="Compare Prompt Versions"
            description="Inspect template and parameter changes before activating a new production prompt contract."
        >
            <x-slot:aside>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-300">Review Rule</p>
                <div class="mt-4 space-y-3 text-sm text-slate-200">
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                        <p class="font-semibold text-white">Compare template and parameters together</p>
                        <p class="mt-1 text-slate-300">A small wording change can still alter the output contract and downstream parser expectations.</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                        <p class="font-semibold text-white">Treat activation as release gating</p>
                        <p class="mt-1 text-slate-300">Use compare mode as the final check before promoting a candidate to active.</p>
                    </div>
                </div>
            </x-slot:aside>
        </x-filament.ui.hero>

        <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
            <x-filament.ui.panel
                eyebrow="Selector"
                title="Choose Baseline And Candidate"
                description="Put the current active version on one side and the candidate on the other."
            >
                <div class="grid gap-3 lg:grid-cols-3">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Prompt family</label>
                        <select wire:model.live="promptName" class="nova-select mt-1">
                            @foreach ($promptNames as $name)
                                <option value="{{ $name }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Baseline</label>
                        <select wire:model.live="leftVersion" class="nova-select mt-1">
                            @foreach ($versionOptions as $version)
                                <option value="{{ $version }}">{{ $version }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Candidate</label>
                        <select wire:model.live="rightVersion" class="nova-select mt-1">
                            @foreach ($versionOptions as $version)
                                <option value="{{ $version }}">{{ $version }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap gap-2">
                    @forelse ($familyVersions as $version)
                        <span @class([
                            'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1',
                            'bg-sky-100 text-sky-700 ring-sky-200 dark:bg-sky-900/30 dark:text-sky-200 dark:ring-sky-800/40' => $leftVersion === $version['version'],
                            'bg-emerald-100 text-emerald-700 ring-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-200 dark:ring-emerald-800/40' => $rightVersion === $version['version'],
                            'bg-amber-100 text-amber-700 ring-amber-200 dark:bg-amber-900/30 dark:text-amber-200 dark:ring-amber-800/40' => $version['is_active'] && $leftVersion !== $version['version'] && $rightVersion !== $version['version'],
                            'bg-gray-100 text-gray-600 ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700' => ! $version['is_active'] && $leftVersion !== $version['version'] && $rightVersion !== $version['version'],
                        ])>
                            {{ $version['version'] }}{{ $version['is_active'] ? ' · active' : '' }}
                        </span>
                    @empty
                        <span class="text-sm text-gray-500 dark:text-gray-400">No versions available for this family yet.</span>
                    @endforelse
                </div>
            </x-filament.ui.panel>

            <x-filament.ui.panel
                eyebrow="Compare Goal"
                title="What To Look For"
                description="The most important signal is not raw length, but how the contract changed."
            >
                <ol class="space-y-3 text-sm text-gray-700 dark:text-gray-200">
                    <li class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                        <span class="font-semibold text-gray-900 dark:text-gray-100">1. Output-shape changes</span>
                        <p class="mt-1">Added or removed parameter keys often mean runtime expectations changed.</p>
                    </li>
                    <li class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                        <span class="font-semibold text-gray-900 dark:text-gray-100">2. Instruction drift</span>
                        <p class="mt-1">Template wording changes can tighten or loosen the generated structure even if the format looks similar.</p>
                    </li>
                    <li class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                        <span class="font-semibold text-gray-900 dark:text-gray-100">3. Activation readiness</span>
                        <p class="mt-1">If compare reveals substantial behavior drift, ship it as an intentional version release, not an inline tweak.</p>
                    </li>
                </ol>
            </x-filament.ui.panel>
        </section>

        @if ($leftPrompt && $rightPrompt)
            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-filament.ui.metric-card
                    label="Parameters Added"
                    :value="count($parameterDiff['added'])"
                    description="Keys present only in the candidate version."
                    tone="emerald"
                />
                <x-filament.ui.metric-card
                    label="Parameters Removed"
                    :value="count($parameterDiff['removed'])"
                    description="Keys missing from the candidate version."
                    tone="rose"
                />
                <x-filament.ui.metric-card
                    label="Parameters Changed"
                    :value="count($parameterDiff['changed'])"
                    description="Shared keys whose values changed."
                    tone="amber"
                />
                <x-filament.ui.metric-card
                    label="Template Lines"
                    :value="$templateDiff['left_lines'] . ' -> ' . $templateDiff['right_lines']"
                    description="Line-count delta between baseline and candidate."
                    tone="sky"
                />
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <x-filament.ui.panel
                    eyebrow="Baseline"
                    :title="$leftPrompt['name'] . ' · ' . $leftPrompt['version']"
                    :badge="$leftPrompt['is_active'] ? 'active' : 'historical'"
                    :tone="$leftPrompt['is_active'] ? 'emerald' : 'default'"
                >
                    <p class="text-xs uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">Updated {{ $leftPrompt['updated_at'] ?: 'n/a' }}</p>
                    <pre class="nova-code-block mt-4 max-h-[32rem] overflow-auto"><code>{{ $leftPrompt['template'] }}</code></pre>
                </x-filament.ui.panel>

                <x-filament.ui.panel
                    eyebrow="Candidate"
                    :title="$rightPrompt['name'] . ' · ' . $rightPrompt['version']"
                    :badge="$rightPrompt['is_active'] ? 'active' : 'candidate'"
                    :tone="$rightPrompt['is_active'] ? 'emerald' : 'sky'"
                >
                    <p class="text-xs uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">Updated {{ $rightPrompt['updated_at'] ?: 'n/a' }}</p>
                    <pre class="nova-code-block mt-4 max-h-[32rem] overflow-auto"><code>{{ $rightPrompt['template'] }}</code></pre>
                </x-filament.ui.panel>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                <div class="space-y-6">
                    <x-filament.ui.panel
                        eyebrow="Parameters"
                        title="Key-Level Changes"
                        description="Changed values are the clearest signal that behavior drifted."
                    >
                        <div class="grid gap-4 lg:grid-cols-3">
                            <div class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4 dark:border-emerald-800/40 dark:bg-emerald-900/10">
                                <p class="text-sm font-semibold text-emerald-900 dark:text-emerald-100">Added</p>
                                <ul class="mt-3 space-y-1 text-sm text-emerald-800 dark:text-emerald-200">
                                    @forelse ($parameterDiff['added'] as $item)
                                        <li><code>{{ $item }}</code></li>
                                    @empty
                                        <li class="opacity-70">No added keys.</li>
                                    @endforelse
                                </ul>
                            </div>
                            <div class="rounded-2xl border border-rose-200 bg-rose-50/70 p-4 dark:border-rose-800/40 dark:bg-rose-900/10">
                                <p class="text-sm font-semibold text-rose-900 dark:text-rose-100">Removed</p>
                                <ul class="mt-3 space-y-1 text-sm text-rose-800 dark:text-rose-200">
                                    @forelse ($parameterDiff['removed'] as $item)
                                        <li><code>{{ $item }}</code></li>
                                    @empty
                                        <li class="opacity-70">No removed keys.</li>
                                    @endforelse
                                </ul>
                            </div>
                            <div class="rounded-2xl border border-amber-200 bg-amber-50/70 p-4 dark:border-amber-800/40 dark:bg-amber-900/10">
                                <p class="text-sm font-semibold text-amber-900 dark:text-amber-100">Changed</p>
                                <ul class="mt-3 space-y-1 text-sm text-amber-800 dark:text-amber-200">
                                    @forelse ($parameterDiff['changed'] as $item)
                                        <li><code>{{ $item }}</code></li>
                                    @empty
                                        <li class="opacity-70">No changed keys.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>

                        <div class="mt-5 space-y-3">
                            @forelse ($parameterValueDiffs as $diff)
                                <div class="rounded-2xl border border-gray-200 px-4 py-4 dark:border-gray-700">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100"><code>{{ $diff['key'] }}</code></p>
                                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold text-amber-700 dark:bg-amber-900/30 dark:text-amber-200">
                                            changed
                                        </span>
                                    </div>
                                    <div class="mt-3 grid gap-3 lg:grid-cols-2">
                                        <div class="rounded-xl bg-gray-50 px-3 py-3 dark:bg-gray-800">
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">Baseline</p>
                                            <p class="mt-2 break-all text-sm text-gray-700 dark:text-gray-200">{{ $diff['left'] }}</p>
                                        </div>
                                        <div class="rounded-xl bg-sky-50 px-3 py-3 dark:bg-sky-950/20">
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-sky-700 dark:text-sky-200">Candidate</p>
                                            <p class="mt-2 break-all text-sm text-sky-800 dark:text-sky-100">{{ $diff['right'] }}</p>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-2xl bg-gray-50 px-4 py-4 text-sm text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                    No shared parameter values changed between these two versions.
                                </div>
                            @endforelse
                        </div>
                    </x-filament.ui.panel>
                </div>

                <div class="space-y-6">
                    <x-filament.ui.panel
                        tone="emerald"
                        eyebrow="Template Diff"
                        title="Additions Preview"
                    >
                        <ul class="space-y-1.5 text-sm text-emerald-800 dark:text-emerald-200">
                            @forelse ($templateDiff['added_preview'] as $line)
                                <li>+ {{ $line }}</li>
                            @empty
                                <li class="opacity-70">No additions detected.</li>
                            @endforelse
                        </ul>
                    </x-filament.ui.panel>

                    <x-filament.ui.panel
                        tone="rose"
                        eyebrow="Template Diff"
                        title="Removals Preview"
                    >
                        <ul class="space-y-1.5 text-sm text-rose-800 dark:text-rose-200">
                            @forelse ($templateDiff['removed_preview'] as $line)
                                <li>- {{ $line }}</li>
                            @empty
                                <li class="opacity-70">No removals detected.</li>
                            @endforelse
                        </ul>
                    </x-filament.ui.panel>

                    <x-filament.ui.panel
                        eyebrow="Activation Readiness"
                        title="Release Signal"
                        :badge="count($parameterDiff['changed']) + count($parameterDiff['added']) + count($parameterDiff['removed']) === 0 ? 'low drift' : 'review carefully'"
                        :tone="count($parameterDiff['changed']) + count($parameterDiff['added']) + count($parameterDiff['removed']) === 0 ? 'emerald' : 'amber'"
                    >
                        <p class="text-sm leading-7 text-gray-700 dark:text-gray-200">
                            @if (count($parameterDiff['changed']) + count($parameterDiff['added']) + count($parameterDiff['removed']) === 0)
                                Parameter contract stayed stable. The main remaining check is whether template wording still preserves the expected output behavior.
                            @else
                                The contract changed at the parameter level. Treat this candidate as a real release and validate downstream expectations before activation.
                            @endif
                        </p>
                    </x-filament.ui.panel>
                </div>
            </section>
        @else
            <x-filament.ui.panel
                tone="amber"
                eyebrow="Compare State"
                title="Choose Prompt Versions"
                description="Select a prompt family and two versions to render the template and parameter diff."
            >
                <div class="grid gap-3 md:grid-cols-3">
                    <div class="rounded-2xl bg-amber-50 px-4 py-4 text-sm text-amber-900 dark:bg-amber-900/15 dark:text-amber-100">
                        Pick a prompt family first.
                    </div>
                    <div class="rounded-2xl bg-gray-50 px-4 py-4 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        Use the active version on one side and a candidate on the other.
                    </div>
                    <div class="rounded-2xl bg-gray-50 px-4 py-4 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        Review both template and parameter shifts before activation.
                    </div>
                </div>
            </x-filament.ui.panel>
        @endif
    </div>
</x-filament-panels::page>
