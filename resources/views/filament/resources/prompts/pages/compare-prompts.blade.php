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
                        <p class="font-semibold text-white">Compare both template and parameters</p>
                        <p class="mt-1 text-slate-300">A “small” wording change can still alter the output contract significantly.</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                        <p class="font-semibold text-white">Treat activation as a release</p>
                        <p class="mt-1 text-slate-300">Use comparison as the final gate before promoting a version to active status.</p>
                    </div>
                </div>
            </x-slot:aside>
        </x-filament.ui.hero>

        <x-filament.ui.panel
            eyebrow="Selector"
            title="Choose Versions"
        >
            <div class="grid gap-3 lg:grid-cols-3">
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Prompt name</label>
                    <select wire:model.live="promptName" class="nova-select mt-1">
                        @foreach ($promptNames as $name)
                            <option value="{{ $name }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Left version</label>
                    <select wire:model.live="leftVersion" class="nova-select mt-1">
                        @foreach ($versionOptions as $version)
                            <option value="{{ $version }}">{{ $version }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Right version</label>
                    <select wire:model.live="rightVersion" class="nova-select mt-1">
                        @foreach ($versionOptions as $version)
                            <option value="{{ $version }}">{{ $version }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </x-filament.ui.panel>

        @if ($leftPrompt && $rightPrompt)
            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-filament.ui.metric-card
                    label="Parameters Added"
                    :value="count($parameterDiff['added'])"
                    description="Keys present only in the right version."
                    tone="emerald"
                />
                <x-filament.ui.metric-card
                    label="Parameters Removed"
                    :value="count($parameterDiff['removed'])"
                    description="Keys present only in the left version."
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
                    description="Line-count delta between selected templates."
                    tone="sky"
                />
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <x-filament.ui.panel
                    eyebrow="Left Version"
                    :title="$leftPrompt['name'] . ' · ' . $leftPrompt['version']"
                    :badge="$leftPrompt['is_active'] ? 'active' : 'historical'"
                    :tone="$leftPrompt['is_active'] ? 'emerald' : 'default'"
                >
                    <p class="text-xs text-gray-500 dark:text-gray-400">Updated {{ $leftPrompt['updated_at'] ?: 'n/a' }}</p>
                    <pre class="nova-code-block mt-4"><code>{{ $leftPrompt['template'] }}</code></pre>
                </x-filament.ui.panel>

                <x-filament.ui.panel
                    eyebrow="Right Version"
                    :title="$rightPrompt['name'] . ' · ' . $rightPrompt['version']"
                    :badge="$rightPrompt['is_active'] ? 'active' : 'candidate'"
                    :tone="$rightPrompt['is_active'] ? 'emerald' : 'sky'"
                >
                    <p class="text-xs text-gray-500 dark:text-gray-400">Updated {{ $rightPrompt['updated_at'] ?: 'n/a' }}</p>
                    <pre class="nova-code-block mt-4"><code>{{ $rightPrompt['template'] }}</code></pre>
                </x-filament.ui.panel>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                <div class="space-y-6">
                    <x-filament.ui.panel
                        eyebrow="Parameters"
                        title="Key-Level Changes"
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
                </div>
            </section>
        @else
            <x-filament.ui.panel
                tone="amber"
                eyebrow="Compare State"
                title="Choose Prompt Versions"
                description="Select a prompt name and two versions to render the template and parameter diff."
            >
                <div class="grid gap-3 md:grid-cols-3">
                    <div class="rounded-2xl bg-amber-50 px-4 py-4 text-sm text-amber-900 dark:bg-amber-900/15 dark:text-amber-100">
                        Pick a prompt name first.
                    </div>
                    <div class="rounded-2xl bg-gray-50 px-4 py-4 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        Use the newest version on one side and the currently active version on the other.
                    </div>
                    <div class="rounded-2xl bg-gray-50 px-4 py-4 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        Review both template and parameter shifts before activation.
                    </div>
                </div>
            </x-filament.ui.panel>
        @endif
    </div>
</x-filament-panels::page>
