@php
    $user = filament()->auth()->user();
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament.ui.hero
            tone="sky"
            eyebrow="Account"
            title="Profile Settings"
            description="Manage your identity, password, and personal access posture inside the same admin shell you use for content and operations."
        >
            <x-slot:aside>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-300">Account Snapshot</p>
                <div class="mt-4 space-y-3 text-sm text-slate-200">
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                        <p class="font-semibold text-white">{{ filament()->getUserName($user) }}</p>
                        <p class="mt-1 text-slate-300">{{ $user?->email ?? 'n/a' }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                        <p class="font-semibold text-white">Role</p>
                        <p class="mt-1 text-slate-300">{{ method_exists($user, 'roleLabel') ? $user->roleLabel() : 'User' }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                        <p class="font-semibold text-white">Security rule</p>
                        <p class="mt-1 text-slate-300">Change identity and password here before touching tokens, prompts, or runtime settings.</p>
                    </div>
                </div>
            </x-slot:aside>

            <div class="mt-5 flex flex-wrap gap-2">
                <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-slate-200 ring-1 ring-white/10">
                    Identity
                </span>
                <span class="inline-flex items-center rounded-full bg-sky-400/15 px-3 py-1 text-xs font-medium text-sky-100 ring-1 ring-sky-300/20">
                    Credentials
                </span>
                <span class="inline-flex items-center rounded-full bg-emerald-400/15 px-3 py-1 text-xs font-medium text-emerald-100 ring-1 ring-emerald-300/20">
                    Access posture
                </span>
            </div>
        </x-filament.ui.hero>

        <section class="grid gap-6 xl:grid-cols-[1.25fr_0.95fr]">
            <x-filament.ui.panel
                eyebrow="Profile Form"
                title="Update Account"
                description="Personal changes here affect your own session and identity, not the platform-wide runtime baseline."
            >
                {{ $this->content }}
            </x-filament.ui.panel>

            <div class="space-y-6">
                <x-filament.ui.panel
                    eyebrow="Security Posture"
                    title="What This Page Controls"
                >
                    <div class="space-y-3">
                        <div class="nova-signal-card">
                            <p class="nova-signal-card-title">Identity data</p>
                            <p class="nova-signal-card-copy">Name and email determine how you appear in the panel and how notifications can reach you.</p>
                        </div>
                        <div class="nova-signal-card">
                            <p class="nova-signal-card-title">Password changes</p>
                            <p class="nova-signal-card-copy">Password updates apply to your session boundary, not to API tokens already issued for integrations.</p>
                        </div>
                        <div class="nova-signal-card">
                            <p class="nova-signal-card-title">Runtime operations stay elsewhere</p>
                            <p class="nova-signal-card-copy">Use API Access, AI Settings, and Prompt Registry for platform controls, not this page.</p>
                        </div>
                    </div>
                </x-filament.ui.panel>

                <x-filament.ui.panel
                    tone="indigo"
                    eyebrow="Routes"
                    title="Move Back Into The Panel"
                >
                    <div class="space-y-3">
                        <a href="{{ filament()->getUrl() }}" class="nova-link-tile">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-950 dark:text-slate-100">Dashboard</p>
                                    <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">Return to the role-aware control room after updating your account.</p>
                                </div>
                                <span class="rounded-full bg-indigo-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-200">
                                    open
                                </span>
                            </div>
                        </a>
                    </div>
                </x-filament.ui.panel>
            </div>
        </section>
    </div>
</x-filament-panels::page>
