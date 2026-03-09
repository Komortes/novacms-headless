<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class User extends Authenticatable
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
        ];
    }

    public function apiAccessTokens(): HasMany
    {
        return $this->hasMany(ApiAccessToken::class)->latest('id');
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
