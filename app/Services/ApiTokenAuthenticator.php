<?php

namespace App\Services;

use App\Models\ApiAccessToken;
use App\Models\User;
use Illuminate\Http\Request;

class ApiTokenAuthenticator
{
    public const REQUEST_ATTRIBUTE_TOKEN = 'novacms.api_access_token';

    public const REQUEST_ATTRIBUTE_GUARD = 'novacms.auth_guard';

    public function authenticate(Request $request): ?User
    {
        $plainTextToken = $request->bearerToken();

        if (! is_string($plainTextToken) || trim($plainTextToken) === '') {
            return null;
        }

        $token = $this->resolveToken($plainTextToken);

        if (! $token instanceof ApiAccessToken || ! $token->isUsable()) {
            return null;
        }

        $token->markUsed();
        $request->attributes->set(self::REQUEST_ATTRIBUTE_TOKEN, $token);
        $request->attributes->set(self::REQUEST_ATTRIBUTE_GUARD, 'api-token');

        return $token->user;
    }

    public function currentToken(Request $request): ?ApiAccessToken
    {
        $token = $request->attributes->get(self::REQUEST_ATTRIBUTE_TOKEN);

        return $token instanceof ApiAccessToken ? $token : null;
    }

    public function currentGuard(Request $request): ?string
    {
        $guard = $request->attributes->get(self::REQUEST_ATTRIBUTE_GUARD);

        return is_string($guard) ? $guard : null;
    }

    private function resolveToken(string $plainTextToken): ?ApiAccessToken
    {
        $secret = $this->extractTokenSecret($plainTextToken);

        if ($secret === null) {
            return null;
        }

        $token = ApiAccessToken::query()
            ->with('user')
            ->where('token_hash', hash('sha256', $secret))
            ->first();

        return $token instanceof ApiAccessToken ? $token : null;
    }

    private function extractTokenSecret(string $plainTextToken): ?string
    {
        $token = trim($plainTextToken);

        if (! str_starts_with($token, 'nova_')) {
            return null;
        }

        $payload = substr($token, 5);

        if ($payload === '' || $payload === false) {
            return null;
        }

        if (preg_match('/^\d+\.([A-Za-z0-9]{32,})$/', $payload, $matches) === 1) {
            return $matches[1];
        }

        return preg_match('/^[A-Za-z0-9]{32,}$/', $payload) === 1
            ? $payload
            : null;
    }
}
