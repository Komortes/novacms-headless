<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament.ui.hero
            tone="slate"
            eyebrow="Prompt Revision"
            :title="$record->name . ' · ' . $record->version"
            :description="'Updated ' . ($record->updated_at?->diffForHumans() ?? 'n/a') . ' · ' . ($record->is_active ? 'active version' : 'inactive version')"
        >
            <x-slot:aside>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-300">Version Snapshot</p>
                <div class="mt-4 space-y-3 text-sm text-slate-200">
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                        <p class="font-semibold text-white">Parameters</p>
                        <p class="mt-1 text-slate-300">{{ $parameterCount }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                        <p class="font-semibold text-white">Template lines</p>
                        <p class="mt-1 text-slate-300">{{ $templateLineCount }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                        <p class="font-semibold text-white">Activation state</p>
                        <p class="mt-1 text-slate-300">{{ $record->is_active ? 'Currently active in the registry.' : 'Stored as an inactive historical version.' }}</p>
                    </div>
                </div>
            </x-slot:aside>
        </x-filament.ui.hero>

        <section class="grid gap-6 xl:grid-cols-[1.6fr_0.8fr]">
            <div>
                {{ $this->content }}
            </div>

            <div class="space-y-6 xl:sticky xl:top-6 xl:self-start">
                <x-filament.ui.panel
                    eyebrow="Release Notes"
                    title="Editing Discipline"
                >
                    <ol class="space-y-3 text-sm text-gray-700 dark:text-gray-200">
                        <li class="rounded-2xl border border-gray-200 px-4 py-3 dark:border-gray-700">
                            <span class="font-semibold text-gray-900 dark:text-gray-100">1. Preserve comparability</span>
                            <p class="mt-1">Keep versions diffable by changing only what is necessary in one revision.</p>
                        </li>
                        <li class="rounded-2xl border border-gray-200 px-4 py-3 dark:border-gray-700">
                            <span class="font-semibold text-gray-900 dark:text-gray-100">2. Guard active versions</span>
                            <p class="mt-1">If this version is active, edit it carefully or create a fresh version when the contract changes too much.</p>
                        </li>
                    </ol>
                </x-filament.ui.panel>

                <x-filament.ui.panel
                    eyebrow="Family History"
                    title="Recent Versions"
                    description="Use compare before activating another version in this family."
                >
                    <div class="space-y-3">
                        @foreach ($familyHistory as $item)
                            <div class="rounded-2xl border border-gray-200 px-4 py-3 dark:border-gray-700">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $item['version'] }}</p>
                                        <p class="mt-1 text-xs uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">
                                            {{ $item['template_lines'] }} lines · {{ $item['parameter_count'] }} params · updated {{ $item['updated_at'] }}
                                        </p>
                                    </div>
                                    <span @class([
                                        'rounded-full px-2.5 py-1 text-[11px] font-semibold',
                                        'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200' => $item['is_active'],
                                        'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' => ! $item['is_active'],
                                    ])>
                                        {{ $item['is_active'] ? 'active' : 'history' }}
                                    </span>
                                </div>

                                <div class="mt-3 flex flex-wrap gap-2">
                                    <a
                                        href="{{ $item['edit_url'] }}"
                                        class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                                    >
                                        Open
                                    </a>
                                    <a
                                        href="{{ $item['compare_url'] }}"
                                        class="inline-flex items-center rounded-full bg-sky-100 px-3 py-1.5 text-xs font-semibold text-sky-700 transition hover:bg-sky-200 dark:bg-sky-900/30 dark:text-sky-200 dark:hover:bg-sky-900/50"
                                    >
                                        Compare
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-filament.ui.panel>
            </div>
        </section>
    </div>
</x-filament-panels::page>
