<?php

namespace App\GraphQL\Directives;

use App\Services\ApiTokenAuthenticator;
use Illuminate\Auth\Access\AuthorizationException;
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
Require a specific ability when the current GraphQL request is authenticated through an API token.
Session-authenticated users bypass this directive.
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

                if ($token !== null && ! $token->can($ability)) {
                    throw new AuthorizationException("API token is missing required ability [{$ability}].");
                }
            }

            return $resolver($root, $args, $context, $resolveInfo);
        });
    }
}
