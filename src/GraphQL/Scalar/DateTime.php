<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Scalar;

use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Utils\Utils;
use Overblog\GraphQLBundle\Annotation as GQL;

#[GQL\Scalar(name: 'DateTime')]
#[GQL\Description('DateTime scalar type')]
class DateTime
{
    public const FORMAT = 'Y-m-d H:i:s';

    public static function serialize($value)
    {
        if (!$value instanceof \DateTime) {
            throw new InvariantViolation('DateTime is not an instance of DateTimeImmutable: '.Utils::printSafe($value));
        }

        return $value->format(self::FORMAT);
    }

    public static function parseValue($value): ?\DateTime
    {
        $date = \DateTime::createFromFormat(self::FORMAT, $value) ?: null;
        if ($date) {
            return $date;
        }

        try {
            return new \DateTime($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function parseLiteral($ast): ?string
    {
        if ($ast instanceof StringValueNode) {
            return $ast->value;
        }

        return null;
    }
}
