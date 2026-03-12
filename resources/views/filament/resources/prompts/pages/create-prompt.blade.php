<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament.ui.hero
            tone="sky"
            eyebrow="Prompt Authoring"
            title="Create Prompt Version"
            description="Add a new versioned prompt contract with explicit instructions, stable parameter keys, and clear output rules."
        >
            <x-slot:aside>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-300">Checklist</p>
                <ol class="mt-4 space-y-3 text-sm text-slate-200">
                    @foreach ($editorChecklist as $item)
                        <li class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">{{ $item }}</li>
                    @endforeach
                </ol>
            </x-slot:aside>
        </x-filament.ui.hero>

        <section class="grid gap-6 xl:grid-cols-[1.6fr_0.8fr]">
            <div>
                {{ $this->content }}
            </div>

            <div class="space-y-6 xl:sticky xl:top-6 xl:self-start">
                <x-filament.ui.panel
                    eyebrow="Authoring Notes"
                    title="Good Prompt Hygiene"
                >
                    <div class="space-y-3 text-sm text-gray-700 dark:text-gray-200">
                        <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">Be explicit</p>
                            <p class="mt-1">If output must be JSON, say exactly what keys, shapes, and constraints are required.</p>
                        </div>
                        <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">Version on behavior change</p>
                            <p class="mt-1">If you change the contract, make it a real version bump instead of mutating history.</p>
                        </div>
                    </div>
                </x-filament.ui.panel>
            </div>
        </section>
    </div>
</x-filament-panels::page>
