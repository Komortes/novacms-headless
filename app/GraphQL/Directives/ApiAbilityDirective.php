<?php

namespace App\GraphQL\Directives;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\ApiTokenAuthenticator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Nuwave\Lighthouse\Execution\ResolveInfo;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class ApiAbilityDirective extends BaseDirective implements FieldMiddleware
{
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'GRAPHQL'
"""
Require a specific ability for the current GraphQL request.
API-token clients must hold the ability on their token; session-authenticated
users must have a role that maps to the same ability.
"""
directive @apiAbility(
  """
  Required token ability, e.g. "graphql:write".
  """
  ability: String!
) repeatable on FIELD_DEFINITION
GRAPHQL;
    }

    public function handleField(FieldValue $fieldValue): void
    {
        $ability = (string) $this->directiveArgValue('ability');

        $fieldValue->wrapResolver(fn (callable $resolver): \Closure => function (mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo) use ($resolver, $ability) {
            $request = $context->request();

            if ($request !== null) {
                $token = app(ApiTokenAuthenticator::class)->currentToken($request);

                if ($token !== null) {
                    if (! $token->can($ability)) {
                        // Prevent markUsed from firing — the request was rejected.
                        $request->attributes->remove('novacms.pending_mark_used');
                        throw new AuthorizationException("API token is missing required ability [{$ability}].");
                    }
                } elseif (! $this->sessionUserSatisfies($context->user(), $ability)) {
                    throw new AuthorizationException("Your role does not grant the required ability [{$ability}].");
                }
            }

            return $resolver($root, $args, $context, $resolveInfo);
        });
    }

    private function sessionUserSatisfies(?Authenticatable $user, string $ability): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return match ($ability) {
            'graphql:read-internal' => $user->canAccessAdminPanel(),
            'graphql:write' => $user->canCreateContent(),
            default => $user->hasRole(UserRole::ADMIN),
        };
    }
}
