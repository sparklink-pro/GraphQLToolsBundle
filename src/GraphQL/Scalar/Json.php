<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Scalar;

use GraphQL\Language\AST\BooleanValueNode;
use GraphQL\Language\AST\FloatValueNode;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\ListValueNode;
use GraphQL\Language\AST\ObjectValueNode;
use GraphQL\Language\AST\StringValueNode;
use Overblog\GraphQLBundle\Annotation as GQL;

#[GQL\Scalar]
#[GQL\Description('Json scalar type')]
class Json
{
    public static function serialize($value)
    {
        return $value;
    }

    public static function parseValue($value)
    {
        return $value;
    }

    public static function parseLiteral($valueNode)
    {
        switch ($valueNode) {
            case $valueNode instanceof StringValueNode:
            case $valueNode instanceof BooleanValueNode:
                return $valueNode->value;
            case $valueNode instanceof IntValueNode:
            case $valueNode instanceof FloatValueNode:
                return \floatval($valueNode->value);
            case $valueNode instanceof ObjectValueNode:
                    $value = [];
                    foreach ($valueNode->fields as $field) {
                        $value[$field->name->value] = self::parseLiteral($field->value);
                    }

                    return $value;

            case $valueNode instanceof ListValueNode:
                return \array_map([self, 'parseLiteral'], $valueNode->values);
            default:
                return null;
        }
    }
}
