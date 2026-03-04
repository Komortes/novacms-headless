<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="grid gap-3 lg:grid-cols-3">
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Prompt name</label>
                    <select wire:model.live="promptName" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                        @foreach ($promptNames as $name)
                            <option value="{{ $name }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Left version</label>
                    <select wire:model.live="leftVersion" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                        @foreach ($versionOptions as $version)
                            <option value="{{ $version }}">{{ $version }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Right version</label>
                    <select wire:model.live="rightVersion" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                        @foreach ($versionOptions as $version)
                            <option value="{{ $version }}">{{ $version }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </section>

        @if ($leftPrompt && $rightPrompt)
            <section class="grid gap-4 lg:grid-cols-4">
                <article class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm dark:border-emerald-800/60 dark:bg-emerald-900/20">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Parameters added</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-900 dark:text-emerald-100">{{ count($parameterDiff['added']) }}</p>
                </article>
                <article class="rounded-xl border border-rose-200 bg-rose-50 p-4 shadow-sm dark:border-rose-800/60 dark:bg-rose-900/20">
                    <p class="text-xs font-semibold uppercase tracking-wide text-rose-700 dark:text-rose-300">Parameters removed</p>
                    <p class="mt-1 text-2xl font-bold text-rose-900 dark:text-rose-100">{{ count($parameterDiff['removed']) }}</p>
                </article>
                <article class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm dark:border-amber-800/60 dark:bg-amber-900/20">
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">Parameters changed</p>
                    <p class="mt-1 text-2xl font-bold text-amber-900 dark:text-amber-100">{{ count($parameterDiff['changed']) }}</p>
                </article>
                <article class="rounded-xl border border-sky-200 bg-sky-50 p-4 shadow-sm dark:border-sky-800/60 dark:bg-sky-900/20">
                    <p class="text-xs font-semibold uppercase tracking-wide text-sky-700 dark:text-sky-300">Template lines</p>
                    <p class="mt-1 text-sm font-semibold text-sky-900 dark:text-sky-100">{{ $templateDiff['left_lines'] }} -> {{ $templateDiff['right_lines'] }}</p>
                </article>
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Left: {{ $leftPrompt['version'] }}</h3>
                        @if ($leftPrompt['is_active'])
                            <span class="rounded bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">active</span>
                        @endif
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Updated: {{ $leftPrompt['updated_at'] ?: 'n/a' }}</p>
                    <pre class="mt-3 max-h-[520px] overflow-auto rounded-lg bg-gray-900 p-3 text-xs text-gray-100">{{ $leftPrompt['template'] }}</pre>
                </article>

                <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Right: {{ $rightPrompt['version'] }}</h3>
                        @if ($rightPrompt['is_active'])
                            <span class="rounded bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">active</span>
                        @endif
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Updated: {{ $rightPrompt['updated_at'] ?: 'n/a' }}</p>
                    <pre class="mt-3 max-h-[520px] overflow-auto rounded-lg bg-gray-900 p-3 text-xs text-gray-100">{{ $rightPrompt['template'] }}</pre>
                </article>
            </section>

            <section class="grid gap-6 lg:grid-cols-3">
                <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Added parameter keys</h3>
                    <ul class="mt-2 space-y-1 text-sm text-gray-700 dark:text-gray-200">
                        @forelse ($parameterDiff['added'] as $item)
                            <li><code>{{ $item }}</code></li>
                        @empty
                            <li class="text-gray-500 dark:text-gray-400">No added keys.</li>
                        @endforelse
                    </ul>
                </article>
                <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Removed parameter keys</h3>
                    <ul class="mt-2 space-y-1 text-sm text-gray-700 dark:text-gray-200">
                        @forelse ($parameterDiff['removed'] as $item)
                            <li><code>{{ $item }}</code></li>
                        @empty
                            <li class="text-gray-500 dark:text-gray-400">No removed keys.</li>
                        @endforelse
                    </ul>
                </article>
                <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Changed parameter keys</h3>
                    <ul class="mt-2 space-y-1 text-sm text-gray-700 dark:text-gray-200">
                        @forelse ($parameterDiff['changed'] as $item)
                            <li><code>{{ $item }}</code></li>
                        @empty
                            <li class="text-gray-500 dark:text-gray-400">No changed keys.</li>
                        @endforelse
                    </ul>
                </article>
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <article class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm dark:border-emerald-800/50 dark:bg-emerald-900/20">
                    <h3 class="text-sm font-semibold text-emerald-900 dark:text-emerald-100">Template additions (preview)</h3>
                    <ul class="mt-2 space-y-1 text-sm text-emerald-800 dark:text-emerald-200">
                        @forelse ($templateDiff['added_preview'] as $line)
                            <li>+ {{ $line }}</li>
                        @empty
                            <li class="text-emerald-700/70 dark:text-emerald-300/70">No additions detected.</li>
                        @endforelse
                    </ul>
                </article>
                <article class="rounded-xl border border-rose-200 bg-rose-50 p-5 shadow-sm dark:border-rose-800/50 dark:bg-rose-900/20">
                    <h3 class="text-sm font-semibold text-rose-900 dark:text-rose-100">Template removals (preview)</h3>
                    <ul class="mt-2 space-y-1 text-sm text-rose-800 dark:text-rose-200">
                        @forelse ($templateDiff['removed_preview'] as $line)
                            <li>- {{ $line }}</li>
                        @empty
                            <li class="text-rose-700/70 dark:text-rose-300/70">No removals detected.</li>
                        @endforelse
                    </ul>
                </article>
            </section>
        @endif
    </div>
</x-filament-panels::page>
