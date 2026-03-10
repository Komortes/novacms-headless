<?php

namespace App\GraphQL\Scalars;

use GraphQL\Language\AST\BooleanValueNode;
use GraphQL\Language\AST\FloatValueNode;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\ListValueNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NullValueNode;
use GraphQL\Language\AST\ObjectValueNode;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Language\AST\ValueNode;
use GraphQL\Language\AST\VariableNode;
use GraphQL\Type\Definition\ScalarType;

class JsonScalar extends ScalarType
{
    public string $name = 'JSON';

    public function serialize(mixed $value): mixed
    {
        return $value;
    }

    public function parseValue(mixed $value): mixed
    {
        return $value;
    }

    public function parseLiteral(Node $valueNode, ?array $variables = null): mixed
    {
        if (! $valueNode instanceof ValueNode) {
            return null;
        }

        return $this->parseNode($valueNode, $variables);
    }

    private function parseNode(ValueNode $valueNode, ?array $variables = null): mixed
    {
        return match (true) {
            $valueNode instanceof StringValueNode => $valueNode->value,
            $valueNode instanceof IntValueNode => (int) $valueNode->value,
            $valueNode instanceof FloatValueNode => (float) $valueNode->value,
            $valueNode instanceof BooleanValueNode => $valueNode->value,
            $valueNode instanceof NullValueNode => null,
            $valueNode instanceof VariableNode => $variables[$valueNode->name->value] ?? null,
            $valueNode instanceof ListValueNode => array_map(
                fn (ValueNode $node) => $this->parseNode($node, $variables),
                $valueNode->values,
            ),
            $valueNode instanceof ObjectValueNode => collect($valueNode->fields)
                ->mapWithKeys(fn ($field) => [$field->name->value => $this->parseNode($field->value, $variables)])
                ->all(),
            default => null,
        };
    }
}
