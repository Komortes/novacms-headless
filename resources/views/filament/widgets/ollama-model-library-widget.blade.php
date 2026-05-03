<div wire:poll.5s class="space-y-4">

    {{-- Pull form --}}
    <x-filament.ui.panel eyebrow="Ollama" title="Model Library">
        <form wire:submit.prevent="startPull" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-48">
                <label for="newModelName" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1.5">
                    Model name
                </label>
                <input
                    id="newModelName"
                    type="text"
                    wire:model="newModelName"
                    list="ollama-model-suggestions"
                    placeholder="llama3.2:3b"
                    autocomplete="off"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-300/40 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-sky-500"
                >
                <datalist id="ollama-model-suggestions">
                    @foreach ($catalog as $entry)
                        <option value="{{ $entry['id'] }}">
                    @endforeach
                </datalist>
            </div>

            <button
                type="submit"
                class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-400/50 dark:bg-sky-500 dark:hover:bg-sky-400"
            >
                <x-heroicon-m-arrow-down-tray class="size-4" />
                Pull model
            </button>

            @if (! $ollamaReachable)
                <p class="w-full text-xs text-rose-600 dark:text-rose-400">
                    Ollama is not reachable at the configured base URL. Pull jobs will fail until the runtime is restored.
                </p>
            @endif
        </form>

        @if (count($activePulls) > 0)
            <div class="mt-5 space-y-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                    In progress — {{ count($activePulls) }} {{ Str::plural('pull', count($activePulls)) }}
                </p>

                @foreach ($activePulls as $pull)
                    <div class="rounded-xl border border-sky-200 bg-sky-50/70 px-4 py-3 dark:border-sky-800/40 dark:bg-sky-900/10">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $pull->model }}</p>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ $pull->status_text ?: 'Waiting for worker…' }}</p>
                            </div>
                            <span class="shrink-0 text-sm font-bold tabular-nums text-sky-700 dark:text-sky-300">
                                {{ $pull->progress }}%
                            </span>
                        </div>
                        <div class="mt-2.5 h-1.5 overflow-hidden rounded-full bg-sky-200 dark:bg-sky-900/50">
                            <div
                                class="h-full rounded-full bg-sky-500 transition-all duration-700 dark:bg-sky-400"
                                style="width: {{ $pull->progress }}%"
                            ></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament.ui.panel>

    {{-- Catalog --}}
    <x-filament.ui.panel eyebrow="Catalog" title="Available Models">

        {{-- Generation models --}}
        @foreach ([
            'tiny'   => ['label' => 'Tiny',   'desc' => 'Under 2 GB · low memory'],
            'small'  => ['label' => 'Small',  'desc' => '2 – 4 GB · balanced'],
            'medium' => ['label' => 'Medium', 'desc' => '4 – 8 GB · higher quality'],
        ] as $tier => $meta)
            @if (count($catalogByCategory['generation'][$tier]) > 0)
                <div class="mb-5">
                    <div class="mb-2.5 flex items-baseline gap-2">
                        <span class="text-xs font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">{{ $meta['label'] }}</span>
                        <span class="text-xs text-slate-400 dark:text-slate-500">{{ $meta['desc'] }}</span>
                    </div>

                    <div class="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        @foreach ($catalogByCategory['generation'][$tier] as $entry)
                            @include('filament.widgets.partials.catalog-card', ['entry' => $entry])
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach

        {{-- Embeddings --}}
        @if (count($catalogByCategory['embeddings']) > 0)
            <div class="mt-1">
                <div class="mb-2.5 flex items-baseline gap-2">
                    <span class="text-xs font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">Embeddings</span>
                    <span class="text-xs text-slate-400 dark:text-slate-500">Semantic search · vector index</span>
                </div>

                <div class="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($catalogByCategory['embeddings'] as $entry)
                        @include('filament.widgets.partials.catalog-card', ['entry' => $entry])
                    @endforeach
                </div>
            </div>
        @endif

    </x-filament.ui.panel>

    {{-- Installed models + recent history --}}
    <section class="grid gap-4 xl:grid-cols-[1.6fr_1fr]">

        {{-- Installed models --}}
        <x-filament.ui.panel eyebrow="Installed" :title="count($installedModels) . ' ' . Str::plural('model', count($installedModels))">
            @if (count($installedModels) === 0)
                <div class="nova-empty-state">
                    @if ($ollamaReachable)
                        No models installed yet. Use the catalog above to pull one.
                    @else
                        Ollama is unreachable — cannot list installed models.
                    @endif
                </div>
            @else
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($installedModels as $model)
                        @php
                            $bytes = (int) ($model['size'] ?? 0);
                            $sizeLabel = $bytes >= 1_073_741_824
                                ? round($bytes / 1_073_741_824, 1) . ' GB'
                                : round($bytes / 1_048_576) . ' MB';
                        @endphp

                        <div class="rounded-xl border border-slate-200/80 bg-white/80 px-3 py-2.5 dark:border-slate-800/60 dark:bg-slate-950/55">
                            <div class="flex items-start justify-between gap-2">
                                <p class="min-w-0 truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $model['name'] }}</p>
                                <button
                                    wire:click="deleteModel('{{ $model['name'] }}')"
                                    wire:confirm="Delete {{ $model['name'] }}? This cannot be undone."
                                    class="shrink-0 rounded-lg p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-900/20 dark:hover:text-rose-400"
                                    title="Delete"
                                >
                                    <x-heroicon-m-trash class="size-3.5" />
                                </button>
                            </div>

                            <div class="mt-1.5 flex flex-wrap gap-1.5">
                                @if ($model['parameter_size'])
                                    <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                        {{ $model['parameter_size'] }}
                                    </span>
                                @endif
                                <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                    {{ $sizeLabel }}
                                </span>
                                @if ($model['family'])
                                    <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                        {{ $model['family'] }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament.ui.panel>

        {{-- Recent pull history --}}
        <x-filament.ui.panel eyebrow="History" title="Recent Pulls">
            @if (count($recentPulls) === 0)
                <div class="nova-empty-state">
                    No completed pulls yet.
                </div>
            @else
                <div class="space-y-2">
                    @foreach ($recentPulls as $pull)
                        <div @class([
                            'flex items-start justify-between gap-3 rounded-xl border px-3 py-2.5',
                            'border-emerald-200 bg-emerald-50/60 dark:border-emerald-800/40 dark:bg-emerald-900/10' => $pull->isSuccess(),
                            'border-rose-200 bg-rose-50/60 dark:border-rose-800/40 dark:bg-rose-900/10' => $pull->isFailed(),
                        ])>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $pull->model }}</p>
                                @if ($pull->isFailed() && $pull->error)
                                    <p class="mt-0.5 text-xs leading-4 text-rose-700 dark:text-rose-300">
                                        {{ Str::limit($pull->error, 80) }}
                                    </p>
                                @else
                                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                        {{ $pull->updated_at?->diffForHumans() ?? 'n/a' }}
                                    </p>
                                @endif
                            </div>

                            <div class="flex shrink-0 items-center gap-2">
                                <span @class([
                                    'rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide',
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200' => $pull->isSuccess(),
                                    'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200' => $pull->isFailed(),
                                ])>
                                    {{ $pull->status }}
                                </span>
                                <button
                                    wire:click="dismissTask({{ $pull->id }})"
                                    class="rounded p-0.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                                    title="Dismiss"
                                >
                                    <x-heroicon-m-x-mark class="size-3.5" />
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament.ui.panel>

    </section>
</div>
