<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->canAccessAdminPanel();
    }

    public function apiAccessTokens(): HasMany
    {
        return $this->hasMany(ApiAccessToken::class)->latest('id');
    }

    public function roleLabel(): string
    {
        return ($this->role ?? UserRole::EDITOR)->label();
    }

    public function hasRole(UserRole $role): bool
    {
        return $this->role === $role;
    }

    public function canAccessAdminPanel(): bool
    {
        return in_array($this->role, [
            UserRole::ADMIN,
            UserRole::EDITOR,
            UserRole::OPERATOR,
        ], true);
    }

    public function canAccessContentWorkspace(): bool
    {
        return $this->canAccessAdminPanel();
    }

    public function canCreateContent(): bool
    {
        return in_array($this->role, [UserRole::ADMIN, UserRole::EDITOR], true);
    }

    public function canUpdateContent(): bool
    {
        return $this->canCreateContent();
    }

    public function canDeleteContent(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function canChangeContentStatus(): bool
    {
        return $this->canCreateContent();
    }

    public function canQueueSummaries(): bool
    {
        return $this->canAccessContentWorkspace();
    }

    public function canAccessQueueOperations(): bool
    {
        return in_array($this->role, [UserRole::ADMIN, UserRole::OPERATOR], true);
    }

    public function canManageContentCatalog(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function canManageEmbeddings(): bool
    {
        return in_array($this->role, [UserRole::ADMIN, UserRole::OPERATOR], true);
    }

    public function canManageAiSettings(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function canManagePrompts(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function canManageApiAccess(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    /**
     * @param  list<string>  $abilities
     * @return array{access_token: ApiAccessToken, plain_text_token: string}
     */
    public function issueApiToken(string $name, array $abilities = ['*'], ?\DateTimeInterface $expiresAt = null): array
    {
        $secret = Str::random(64);
        $token = $this->apiAccessTokens()->create([
            'name' => $name,
            'token_hash' => hash('sha256', $secret),
            'abilities' => $abilities,
            'expires_at' => $expiresAt,
        ]);

        return [
            'access_token' => $token,
            'plain_text_token' => sprintf('nova_%d.%s', $token->id, $secret),
        ];
    }
}
