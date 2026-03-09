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
        if (! preg_match('/^nova_(\d+)\.([A-Za-z0-9]+)$/', trim($plainTextToken), $matches)) {
            return null;
        }

        $tokenId = (int) $matches[1];
        $secret = $matches[2];

        $token = ApiAccessToken::query()
            ->with('user')
            ->find($tokenId);

        if (! $token instanceof ApiAccessToken) {
            return null;
        }

        return hash_equals($token->token_hash, hash('sha256', $secret))
            ? $token
            : null;
    }
}
