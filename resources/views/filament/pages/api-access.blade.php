<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament.ui.hero
            tone="sky"
            eyebrow="External API Access"
            title="API Token Control"
            description="Issue, review, and retire GraphQL bearer tokens without leaving the admin. Treat this page as the headless delivery registry, not just a create action."
        >
            <x-slot:aside>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-300">Security Notes</p>
                <div class="mt-4 space-y-3 text-sm text-slate-200">
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                        <p class="font-semibold text-white">Least privilege first</p>
                        <p class="mt-1 text-slate-300">Default new clients to read-only access unless a real write contract exists.</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                        <p class="font-semibold text-white">The plain token is shown once</p>
                        <p class="mt-1 text-slate-300">After issuance, only the hash remains in storage. Copy the secret immediately.</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                        <p class="font-semibold text-white">Rotate aggressively</p>
                        <p class="mt-1 text-slate-300">Revoke stale, privileged, or never-used tokens before they become ambient risk.</p>
                    </div>
                </div>
            </x-slot:aside>

            <div class="mt-5 flex flex-wrap gap-2">
                <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-slate-200 ring-1 ring-white/10">
                    Principals {{ $principalCount }}
                </span>
                <span class="inline-flex items-center rounded-full bg-amber-400/15 px-3 py-1 text-xs font-medium text-amber-100 ring-1 ring-amber-300/20">
                    Expiring soon {{ $expiringSoonCount }}
                </span>
                <span class="inline-flex items-center rounded-full bg-rose-400/15 px-3 py-1 text-xs font-medium text-rose-100 ring-1 ring-rose-300/20">
                    High privilege {{ $privilegedCount }}
                </span>
            </div>
        </x-filament.ui.hero>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <x-filament.ui.metric-card
                label="Registry"
                :value="$tokenCount"
                description="All issued tokens currently known to the admin."
                tone="indigo"
            />
            <x-filament.ui.metric-card
                label="Usable"
                :value="$activeCount"
                description="Active tokens that are not expired or revoked."
                tone="emerald"
            />
            <x-filament.ui.metric-card
                label="Expiring Soon"
                :value="$expiringSoonCount"
                description="Usable tokens reaching expiry within 7 days."
                tone="amber"
            />
            <x-filament.ui.metric-card
                label="Never Used"
                :value="$neverUsedCount"
                description="Usable tokens with no recorded request yet."
                tone="sky"
            />
            <x-filament.ui.metric-card
                label="High Privilege"
                :value="$privilegedCount"
                description="Tokens carrying `graphql:admin` or full wildcard access."
                tone="rose"
            />
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
            <x-filament.ui.panel
                eyebrow="Registry Lanes"
                title="Read Token Risk By Segment"
                description="These segments tell you which part of the headless delivery registry needs action first."
            >
                <div class="grid gap-3 md:grid-cols-2">
                    @foreach ($registryLanes as $lane)
                        <a href="{{ $lane['href'] }}" class="nova-link-tile">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $lane['label'] }}</p>
                                    <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $lane['description'] }}</p>
                                </div>
                                <span @class([
                                    'rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide',
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200' => $lane['tone'] === 'emerald',
                                    'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200' => $lane['tone'] === 'amber',
                                    'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-200' => $lane['tone'] === 'rose',
                                    'bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-200' => $lane['tone'] === 'sky',
                                ])>
                                    {{ $lane['count'] }}
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </x-filament.ui.panel>

            <x-filament.ui.panel
                tone="indigo"
                eyebrow="Principal Coverage"
                title="Who Owns Active Clients"
                description="Use this to spot concentration, privilege hotspots, and stale ownership across frontend consumers and integrations."
            >
                @if ($principalSummaries === [])
                    <div class="rounded-2xl border border-dashed border-gray-300 p-6 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
                        No principals have issued tokens yet.
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($principalSummaries as $principal)
                            <article class="rounded-2xl border border-gray-200 p-4 dark:border-gray-700">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $principal['principal'] }}</p>
                                        <p class="mt-1 text-xs uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">last used {{ $principal['last_used'] }}</p>
                                    </div>
                                    <span class="rounded-full bg-indigo-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-200">
                                        {{ $principal['total'] }} total
                                    </span>
                                </div>

                                <div class="mt-4 grid gap-2 sm:grid-cols-3">
                                    <div class="nova-mini-stat">
                                        <p class="nova-mini-stat-label">Usable</p>
                                        <p class="nova-mini-stat-value text-base">{{ $principal['usable'] }}</p>
                                    </div>
                                    <div class="nova-mini-stat">
                                        <p class="nova-mini-stat-label">Privileged</p>
                                        <p class="nova-mini-stat-value text-base">{{ $principal['privileged'] }}</p>
                                    </div>
                                    <div class="nova-mini-stat">
                                        <p class="nova-mini-stat-label">Ownership</p>
                                        <p class="nova-mini-stat-value text-base">{{ $principal['total'] > 1 ? 'clustered' : 'single' }}</p>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </x-filament.ui.panel>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
            <x-filament.ui.panel
                eyebrow="Access Posture"
                title="Recommended Ability Shapes"
                description="Use these as the default contracts when issuing tokens for headless delivery."
            >
                <div class="grid gap-3">
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
                            <p class="mt-3 text-xs leading-5 text-gray-500 dark:text-gray-400">{{ $guide['caution'] }}</p>
                        </article>
                    @endforeach
                </div>
            </x-filament.ui.panel>

            <x-filament.ui.panel
                tone="sky"
                eyebrow="Client Bootstrap"
                title="Smoke-Test New Tokens"
                description="Issue the token, verify auth once, then hand it to the real frontend or integration."
            >
                <div class="space-y-4">
                    @foreach ($clientSnippets as $snippet)
                        <article class="rounded-2xl border border-sky-200/70 bg-white/80 p-4 dark:border-sky-800/30 dark:bg-gray-950/60">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $snippet['title'] }}</p>
                                    <p class="mt-2 text-sm leading-6 text-gray-700 dark:text-gray-200">{{ $snippet['description'] }}</p>
                                </div>
                                <span class="rounded-full bg-sky-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-sky-800 dark:bg-sky-900/30 dark:text-sky-200">
                                    {{ $snippet['language'] }}
                                </span>
                            </div>
                            <pre class="nova-code-block mt-4"><code>{{ $snippet['code'] }}</code></pre>
                        </article>
                    @endforeach
                </div>
            </x-filament.ui.panel>
        </section>

        @if (filled($issuedPlainTextToken) && filled($issuedTokenMeta))
            <x-filament.ui.panel
                tone="emerald"
                eyebrow="Issued Token"
                title="Store This Secret Now"
                badge="shown once"
            >
                <div class="space-y-4">
                    <pre class="nova-code-block"><code>{{ $issuedPlainTextToken }}</code></pre>

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
                </div>
            </x-filament.ui.panel>
        @endif

        <section class="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
            <div class="space-y-6">
                <x-filament.ui.panel
                    eyebrow="Rotation Discipline"
                    title="How To Keep The Registry Safe"
                >
                    <div class="space-y-3">
                        @foreach ($rotationRules as $rule)
                            <div class="nova-signal-card">
                                <p class="nova-signal-card-title">{{ $rule['title'] }}</p>
                                <p class="nova-signal-card-copy">{{ $rule['description'] }}</p>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 grid gap-3 text-sm md:grid-cols-2 xl:grid-cols-1">
                        <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Revoked</p>
                            <p class="mt-2 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $revokedCount }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Retired tokens that cannot be used anymore.</p>
                        </div>
                        <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Expired</p>
                            <p class="mt-2 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $expiredCount }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Historical tokens that need replacement if the client still matters.</p>
                        </div>
                        <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Principals</p>
                            <p class="mt-2 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $principalCount }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Unique users with at least one issued token.</p>
                        </div>
                    </div>
                </x-filament.ui.panel>

                <x-filament.ui.panel
                    id="recent-usage"
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

                                    <div class="mt-4 flex flex-wrap gap-2">
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
                id="token-registry"
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

                                <div class="mt-4 grid gap-2 text-xs sm:grid-cols-3">
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

                                <div class="mt-4 flex flex-wrap gap-2">
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

                                <div class="mt-4 flex flex-wrap gap-2">
                                    @if (! $token->isRevoked())
                                        <x-filament::button
                                            size="xs"
                                            color="danger"
                                            wire:click="revokeToken({{ $token->id }})"
                                        >
                                            Revoke
                                        </x-filament::button>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </x-filament.ui.panel>
        </section>
    </div>
</x-filament-panels::page>
