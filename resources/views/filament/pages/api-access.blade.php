<x-filament-panels::page>
    <div class="space-y-5">
        <x-filament.ui.hero
            tone="sky"
            eyebrow="External API Access"
            title="API Token Control"
            description="Issue, review, and retire GraphQL bearer tokens. Treat this page as the token registry."
        >
            <div class="flex flex-wrap gap-2">
                <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-slate-200 ring-1 ring-white/10">
                    Principals: {{ $principalCount }}
                </span>
                <span class="inline-flex items-center rounded-full bg-amber-400/15 px-3 py-1 text-xs font-medium text-amber-100 ring-1 ring-amber-300/20">
                    Expiring soon: {{ $expiringSoonCount }}
                </span>
                <span class="inline-flex items-center rounded-full bg-rose-400/15 px-3 py-1 text-xs font-medium text-rose-100 ring-1 ring-rose-300/20">
                    High privilege: {{ $privilegedCount }}
                </span>
            </div>
        </x-filament.ui.hero>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <x-filament.ui.metric-card
                label="Registry"
                :value="$tokenCount"
                description="All issued tokens."
                tone="indigo"
            />
            <x-filament.ui.metric-card
                label="Usable"
                :value="$activeCount"
                description="Active, not expired or revoked."
                tone="emerald"
            />
            <x-filament.ui.metric-card
                label="Expiring Soon"
                :value="$expiringSoonCount"
                description="Within 7 days."
                tone="amber"
            />
            <x-filament.ui.metric-card
                label="Never Used"
                :value="$neverUsedCount"
                description="No recorded request yet."
                tone="sky"
            />
            <x-filament.ui.metric-card
                label="High Privilege"
                :value="$privilegedCount"
                description="Admin or wildcard access."
                tone="rose"
            />
        </section>

        <x-filament.ui.panel
            eyebrow="Access Posture"
            title="Recommended Ability Shapes"
            description="Use these as the default contracts when issuing new tokens."
        >
            <div class="grid gap-3 xl:grid-cols-3">
                @foreach ($abilityGuides as $guide)
                    <article @class([
                        'rounded-2xl border p-4',
                        'border-emerald-200 bg-emerald-50/70 dark:border-emerald-800/30 dark:bg-emerald-900/10' => $guide['tone'] === 'emerald',
                        'border-sky-200 bg-sky-50/70 dark:border-sky-800/30 dark:bg-sky-900/10' => $guide['tone'] === 'sky',
                        'border-rose-200 bg-rose-50/70 dark:border-rose-800/30 dark:bg-rose-900/10' => $guide['tone'] === 'rose',
                    ])>
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $guide['label'] }}</p>
                                <p class="mt-2 text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">{{ $guide['abilities'] }}</p>
                            </div>
                        </div>
                        <p class="mt-3 text-sm leading-6 text-gray-700 dark:text-gray-200">{{ $guide['description'] }}</p>
                        <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">{{ $guide['caution'] }}</p>
                    </article>
                @endforeach
            </div>
        </x-filament.ui.panel>

        @if (filled($issuedPlainTextToken) && filled($issuedTokenMeta))
            <x-filament.ui.panel
                tone="emerald"
                eyebrow="Issued Token"
                title="Store This Secret Now"
                badge="shown once"
            >
                <div class="space-y-4">
                    <pre class="nova-code-block overflow-x-auto"><code>{{ $issuedPlainTextToken }}</code></pre>

                    <div class="grid gap-3 text-sm md:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">Token ID</p>
                            <p class="mt-1">{{ $issuedTokenMeta['id'] }}</p>
                        </div>
                        <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">User</p>
                            <p class="mt-1">{{ $issuedTokenMeta['user'] }}</p>
                        </div>
                        <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">Label</p>
                            <p class="mt-1">{{ $issuedTokenMeta['name'] }}</p>
                        </div>
                        <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">Expires</p>
                            <p class="mt-1">{{ $issuedTokenMeta['expires_at'] }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @foreach (($issuedTokenMeta['abilities'] ?? []) as $ability)
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200">{{ $ability }}</span>
                        @endforeach
                    </div>

                    <div class="flex justify-end">
                        <button
                            wire:click="clearIssuedToken"
                            type="button"
                            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2"
                        >
                            I've copied it — dismiss
                        </button>
                    </div>
                </div>
            </x-filament.ui.panel>
        @endif

        <section class="grid gap-5 xl:grid-cols-[0.8fr_1.2fr]">
            <div class="space-y-5">
                <x-filament.ui.panel
                    eyebrow="Lifecycle"
                    title="Registry Posture"
                >
                    <div class="grid gap-3 text-sm">
                        <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Revoked</p>
                            <p class="mt-2 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $revokedCount }}</p>
                        </div>
                        <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Expired</p>
                            <p class="mt-2 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $expiredCount }}</p>
                        </div>
                        <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Principals</p>
                            <p class="mt-2 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $principalCount }}</p>
                        </div>
                    </div>
                </x-filament.ui.panel>

                <x-filament.ui.panel
                    eyebrow="Recent Usage"
                    title="Last Active Clients"
                    :badge="count($recentUsage) . ' recent'"
                >
                    @if ($recentUsage->isEmpty())
                        <div class="rounded-2xl border border-dashed border-gray-300 p-6 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
                            No token usage has been recorded yet.
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($recentUsage as $token)
                                <article class="rounded-2xl border border-gray-200 p-4 dark:border-gray-700">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $token->name }}</p>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                {{ $token->user?->email ?? 'unknown user' }} · {{ $token->last_used_at?->diffForHumans() ?? 'never' }}
                                            </p>
                                        </div>
                                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                            #{{ $token->id }}
                                        </span>
                                    </div>

                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach (($token->abilities ?? []) as $ability)
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $ability }}</span>
                                        @endforeach
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </x-filament.ui.panel>
            </div>

            <x-filament.ui.panel
                eyebrow="Registry"
                title="Recent Tokens"
                :badge="$tokenCount . ' total'"
            >
                @if ($tokens->isEmpty())
                    <div class="rounded-2xl border border-dashed border-gray-300 p-6 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
                        No API tokens have been issued yet.
                    </div>
                @else
                    <div class="grid gap-4 lg:grid-cols-2">
                        @foreach ($tokens as $token)
                            <article @class([
                                'rounded-2xl border p-4',
                                'border-emerald-200 bg-emerald-50/50 dark:border-emerald-800/30 dark:bg-emerald-900/10' => $token->isUsable(),
                                'border-amber-200 bg-amber-50/50 dark:border-amber-800/30 dark:bg-amber-900/10' => $token->isExpired() && ! $token->isRevoked(),
                                'border-rose-200 bg-rose-50/50 dark:border-rose-800/30 dark:bg-rose-900/10' => $token->isRevoked(),
                            ])>
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $token->name }}</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            #{{ $token->id }} · {{ $token->user?->email ?? 'unknown user' }}
                                        </p>
                                    </div>
                                    <span @class([
                                        'rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide',
                                        'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200' => $token->isUsable(),
                                        'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200' => $token->isExpired() && ! $token->isRevoked(),
                                        'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-200' => $token->isRevoked(),
                                    ])>
                                        {{ $token->isRevoked() ? 'revoked' : ($token->isExpired() ? 'expired' : 'usable') }}
                                    </span>
                                </div>

                                <div class="mt-3 grid gap-2 text-xs sm:grid-cols-3">
                                    <div class="rounded-xl bg-white px-3 py-2 dark:bg-gray-950">
                                        <p class="font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Abilities</p>
                                        <p class="mt-1 break-words text-gray-900 dark:text-gray-100">{{ implode(', ', $token->abilities ?? []) ?: 'n/a' }}</p>
                                    </div>
                                    <div class="rounded-xl bg-white px-3 py-2 dark:bg-gray-950">
                                        <p class="font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Last used</p>
                                        <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $token->last_used_at?->diffForHumans() ?? 'never' }}</p>
                                    </div>
                                    <div class="rounded-xl bg-white px-3 py-2 dark:bg-gray-950">
                                        <p class="font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Expires</p>
                                        <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $token->expires_at?->diffForHumans() ?? 'never' }}</p>
                                    </div>
                                </div>

                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach (($token->abilities ?? []) as $ability)
                                        <span @class([
                                            'rounded-full px-2.5 py-1 text-[11px] font-medium',
                                            'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-200' => in_array($ability, ['graphql:admin', '*'], true),
                                            'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200' => ! in_array($ability, ['graphql:admin', '*'], true),
                                        ])>
                                            {{ $ability }}
                                        </span>
                                    @endforeach
                                </div>

                                @if (! $token->isRevoked())
                                    <div class="mt-3">
                                        <x-filament::button
                                            size="xs"
                                            color="danger"
                                            wire:click="revokeToken({{ $token->id }})"
                                            wire:confirm="Revoke token #{{ $token->id }}? This cannot be undone."
                                        >
                                            Revoke
                                        </x-filament::button>
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
            </x-filament.ui.panel>
        </section>
    </div>
</x-filament-panels::page>
