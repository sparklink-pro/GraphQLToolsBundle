<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Scalar;

use GraphQL\Error\Error;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Utils\Utils;
use Overblog\GraphQLBundle\Annotation as GQL;

#[GQL\Scalar]
#[GQL\Description('Email scalar type')]
class Email
{
    public static function serialize($value)
    {
        return $value;
    }

    public static function parseValue($value)
    {
        if (!\filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new Error('Cannot represent following value as email: '.Utils::printSafeJson($value));
        }

        return $value;
    }

    public static function parseLiteral($valueNode)
    {
        // Note: throwing GraphQL\Error\Error vs \UnexpectedValueException to benefit from GraphQL
        // error location in query:
        if (!$valueNode instanceof StringValueNode) {
            throw new Error('Query error: Can only parse strings got: '.$valueNode->kind, [$valueNode]);
        }

        if (!\filter_var($valueNode->value, FILTER_VALIDATE_EMAIL)) {
            throw new Error('Not a valid email', [$valueNode]);
        }

        return $valueNode->value;
    }
}
