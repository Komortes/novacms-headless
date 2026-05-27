<?php

namespace App\Filament\Pages;

use App\Models\ApiAccessToken;
use App\Models\User;
use App\Support\AdminPanelAccess;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;

class ApiAccess extends Page
{
    public ?string $issuedPlainTextToken = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $issuedTokenMeta = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Key;

    protected static ?string $navigationLabel = 'API Access';

    protected static string|\UnitEnum|null $navigationGroup = 'Workspace';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'API Access';

    protected static ?string $slug = 'settings/api-access';

    protected string $view = 'filament.pages.api-access';

    public static function canAccess(): bool
    {
        return AdminPanelAccess::canManageApiAccess();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Issue and revoke external API bearer tokens for GraphQL clients and integrations.';
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('issueToken')
                ->label('Issue token')
                ->icon(Heroicon::Plus)
                ->color('primary')
                ->modalHeading('Issue API token')
                ->modalSubmitActionLabel('Create token')
                ->schema([
                    Select::make('user_id')
                        ->label('User')
                        ->options($this->userOptions())
                        ->searchable()
                        ->preload()
                        ->required()
                        ->native(false),
                    TextInput::make('name')
                        ->label('Token label')
                        ->required()
                        ->maxLength(120)
                        ->placeholder('External app / preview client / integration name'),
                    CheckboxList::make('abilities')
                        ->label('Abilities')
                        ->options($this->abilityOptions())
                        ->descriptions($this->abilityDescriptions())
                        ->default(['graphql:read-internal'])
                        ->columns(1)
                        ->required(),
                    TextInput::make('expires_days')
                        ->label('Expires in days')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(365)
                        ->placeholder('Leave empty for no expiry'),
                ])
                ->action(function (array $data): void {
                    $user = User::query()->find((int) $data['user_id']);

                    if (! $user) {
                        Notification::make()
                            ->title('User not found')
                            ->danger()
                            ->send();

                        return;
                    }

                    $abilities = collect((array) ($data['abilities'] ?? []))
                        ->map(fn (mixed $ability): string => trim((string) $ability))
                        ->filter()
                        ->values()
                        ->all();

                    if ($abilities === []) {
                        $abilities = ['graphql:read-internal'];
                    }

                    $expiresDays = trim((string) ($data['expires_days'] ?? ''));
                    $expiresAt = $expiresDays !== '' ? now()->addDays((int) $expiresDays) : null;

                    $issued = $user->issueApiToken(
                        name: trim((string) $data['name']),
                        abilities: $abilities,
                        expiresAt: $expiresAt,
                    );

                    /** @var ApiAccessToken $token */
                    $token = $issued['access_token'];

                    $this->issuedPlainTextToken = (string) $issued['plain_text_token'];
                    $this->issuedTokenMeta = [
                        'id' => $token->id,
                        'user' => $user->email,
                        'name' => $token->name,
                        'abilities' => $abilities,
                        'expires_at' => $token->expires_at?->toDateTimeString() ?? 'never',
                    ];

                    Notification::make()
                        ->title('API token created')
                        ->body('Store the plain token now. It will not be shown again after you leave this page.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = ApiAccessToken::query()
            ->whereNull('revoked_at')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $expired = ApiAccessToken::query()
            ->whereNull('revoked_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->count();

        return $expired > 0 ? 'warning' : 'gray';
    }

    public function clearIssuedToken(): void
    {
        $this->issuedPlainTextToken = null;
        $this->issuedTokenMeta = null;
    }

    public function revokeToken(int $tokenId): void
    {
        abort_unless(AdminPanelAccess::canManageApiAccess(), 403);

        $token = ApiAccessToken::query()->find($tokenId);

        if (! $token) {
            return;
        }

        if ($token->revoked_at !== null) {
            Notification::make()
                ->title('Token already revoked')
                ->warning()
                ->send();

            return;
        }

        $token->revoke();

        Notification::make()
            ->title('Token revoked')
            ->body("Token #{$token->id} can no longer be used.")
            ->success()
            ->send();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $allTokens = ApiAccessToken::query()
            ->with('user')
            ->latest('id')
            ->get();
        $tokens = $allTokens->take(24)->values();
        $now = now();
        $expiringThreshold = $now->copy()->addDays(7);

        $activeCount = $allTokens->filter(fn (ApiAccessToken $token): bool => $token->isUsable())->count();
        $revokedCount = $allTokens->filter(fn (ApiAccessToken $token): bool => $token->isRevoked())->count();
        $expiredCount = $allTokens->filter(fn (ApiAccessToken $token): bool => $token->isExpired() && ! $token->isRevoked())->count();
        $expiringSoonCount = $allTokens->filter(
            fn (ApiAccessToken $token): bool => ! $token->isRevoked()
                && ! $token->isExpired()
                && $token->expires_at instanceof Carbon
                && $token->expires_at->betweenIncluded($now, $expiringThreshold)
        )->count();
        $neverUsedCount = $allTokens->filter(
            fn (ApiAccessToken $token): bool => $token->isUsable() && $token->last_used_at === null
        )->count();
        $privilegedCount = $allTokens->filter(
            fn (ApiAccessToken $token): bool => $this->isPrivilegedToken($token)
        )->count();
        $principalCount = $allTokens
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->count();
        $recentUsage = $allTokens
            ->filter(fn (ApiAccessToken $token): bool => $token->last_used_at !== null)
            ->sortByDesc('last_used_at')
            ->take(6)
            ->values();

        return [
            'activeCount' => $activeCount,
            'revokedCount' => $revokedCount,
            'expiredCount' => $expiredCount,
            'expiringSoonCount' => $expiringSoonCount,
            'neverUsedCount' => $neverUsedCount,
            'privilegedCount' => $privilegedCount,
            'principalCount' => $principalCount,
            'tokenCount' => $allTokens->count(),
            'tokens' => $tokens,
            'recentUsage' => $recentUsage,
            'abilityGuides' => $this->abilityGuides(),
            'clientSnippets' => $this->clientSnippets(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function userOptions(): array
    {
        return User::query()
            ->orderBy('name')
            ->orderBy('email')
            ->get()
            ->mapWithKeys(fn (User $user): array => [
                (int) $user->id => trim(($user->name ? $user->name.' · ' : '').$user->email),
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function abilityOptions(): array
    {
        return [
            'graphql:read-internal' => 'graphql:read-internal',
            'graphql:write' => 'graphql:write',
            'graphql:admin' => 'graphql:admin',
            '*' => '*',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function abilityDescriptions(): array
    {
        return [
            'graphql:read-internal' => 'Read internal content, including drafts and operator-facing queries.',
            'graphql:write' => 'Use write mutations such as content creation and updates.',
            'graphql:admin' => 'Access admin-level GraphQL operations.',
            '*' => 'Full token access. Use only for trusted internal clients.',
        ];
    }

    private function isPrivilegedToken(ApiAccessToken $token): bool
    {
        $abilities = collect($token->abilities ?? [])
            ->map(fn (mixed $ability): string => trim((string) $ability))
            ->filter()
            ->values()
            ->all();

        return in_array('*', $abilities, true) || in_array('graphql:admin', $abilities, true);
    }

    /**
     * @return list<array{label: string, abilities: string, description: string, caution: string, tone: string}>
     */
    private function abilityGuides(): array
    {
        return [
            [
                'label' => 'Preview / read clients',
                'abilities' => 'graphql:read-internal',
                'description' => 'Safe default for frontends, preview tools, and reporting consumers that should never mutate content.',
                'caution' => 'Prefer this whenever the client only reads drafts or operator-facing GraphQL fields.',
                'tone' => 'emerald',
            ],
            [
                'label' => 'Trusted write clients',
                'abilities' => 'graphql:read-internal + graphql:write',
                'description' => 'For internal automations that create or update content through explicit GraphQL mutations.',
                'caution' => 'Use only when the integration needs write paths and the caller identity is controlled.',
                'tone' => 'sky',
            ],
            [
                'label' => 'Administrative clients',
                'abilities' => 'graphql:admin or *',
                'description' => 'Reserved for tightly scoped internal tooling, migrations, or operator-only recovery paths.',
                'caution' => 'Track these aggressively. They are the first tokens to rotate or revoke after any incident.',
                'tone' => 'rose',
            ],
        ];
    }

    /**
     * @return list<array{title: string, description: string, language: string, code: string}>
     */
    private function clientSnippets(): array
    {
        return [
            [
                'title' => 'cURL smoke check',
                'description' => 'Fastest way to verify bearer auth and confirm the token can read data.',
                'language' => 'bash',
                'code' => <<<'BASH'
curl https://example.test/graphql \
  -H "Authorization: Bearer nova_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  --data '{"query":"{ contents(first: 3) { data { id title status } } }"}'
BASH,
            ],
            [
                'title' => 'JavaScript client bootstrap',
                'description' => 'Minimal fetch example for preview apps or lightweight integrations.',
                'language' => 'js',
                'code' => <<<'JS'
const response = await fetch('https://example.test/graphql', {
  method: 'POST',
  headers: {
    Authorization: 'Bearer nova_TOKEN_HERE',
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    query: '{ contents(first: 5) { data { id title slug } } }',
  }),
});

const payload = await response.json();
console.log(payload);
JS,
            ],
        ];
    }
}
