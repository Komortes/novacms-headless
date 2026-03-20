@php
    $user = filament()->auth()->user();
@endphp

<x-filament-widgets::widget class="fi-account-widget">
    <x-filament.ui.panel
        tone="indigo"
        eyebrow="Account"
        :title="filament()->getUserName($user)"
        :description="$user?->email"
    >
        <div class="grid gap-3 md:grid-cols-3">
            <div class="nova-mini-stat">
                <p class="nova-mini-stat-label">Role</p>
                <p class="nova-mini-stat-value text-base">{{ method_exists($user, 'roleLabel') ? $user->roleLabel() : 'User' }}</p>
            </div>
            <div class="nova-mini-stat">
                <p class="nova-mini-stat-label">Panel</p>
                <p class="nova-mini-stat-value text-base">Admin</p>
            </div>
            <div class="nova-mini-stat">
                <p class="nova-mini-stat-label">Access</p>
                <p class="nova-mini-stat-value text-base">Authenticated</p>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            @if (filament()->hasProfile())
                <x-filament::button
                    color="gray"
                    size="sm"
                    tag="a"
                    :href="filament()->getProfileUrl()"
                    :icon="\Filament\Support\Icons\Heroicon::UserCircle"
                >
                    Account
                </x-filament::button>
            @endif

            <form action="{{ filament()->getLogoutUrl() }}" method="post">
                @csrf

                <x-filament::button
                    color="gray"
                    size="sm"
                    tag="button"
                    type="submit"
                    :icon="\Filament\Support\Icons\Heroicon::ArrowLeftEndOnRectangle"
                >
                    Logout
                </x-filament::button>
            </form>
        </div>
    </x-filament.ui.panel>
</x-filament-widgets::widget>
