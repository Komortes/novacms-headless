<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament.ui.hero
            tone="sky"
            eyebrow="External API Access"
            title="API Tokens"
            description="Issue bearer tokens for external GraphQL clients, preview frontends, and internal integrations without dropping to the CLI."
        >
            <x-slot:aside>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-300">Security Notes</p>
                <div class="mt-4 space-y-3 text-sm text-slate-200">
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                        <p class="font-semibold text-white">Least privilege first</p>
                        <p class="mt-1 text-slate-300">Use only the abilities the client actually needs.</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                        <p class="font-semibold text-white">The plain token is shown once</p>
                        <p class="mt-1 text-slate-300">After issuance, store it immediately. Only the hash is kept in the database.</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                        <p class="font-semibold text-white">Revocation is instant</p>
                        <p class="mt-1 text-slate-300">Revoke tokens from this page when a client is retired or compromised.</p>
                    </div>
                </div>
            </x-slot:aside>
        </x-filament.ui.hero>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-filament.ui.metric-card
                label="Listed Tokens"
                :value="$tokenCount"
                description="Recent tokens visible on this page."
                tone="indigo"
            />
            <x-filament.ui.metric-card
                label="Usable"
                :value="$activeCount"
                description="Currently active and not expired."
                tone="emerald"
            />
            <x-filament.ui.metric-card
                label="Expired"
                :value="$expiredCount"
                description="Expired tokens that should be replaced or cleaned up."
                tone="amber"
            />
            <x-filament.ui.metric-card
                label="Revoked"
                :value="$revokedCount"
                description="Tokens intentionally disabled."
                tone="rose"
            />
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

                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4 text-sm text-gray-700 dark:text-gray-200">
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

        <x-filament.ui.panel
            eyebrow="Registry"
            title="Recent Tokens"
            :badge="$tokenCount . ' shown'"
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
    </div>
</x-filament-panels::page>
