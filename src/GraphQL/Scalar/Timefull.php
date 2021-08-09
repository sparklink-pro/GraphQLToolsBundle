<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Scalar;

use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Utils\Utils;
use Overblog\GraphQLBundle\Annotation as GQL;

#[GQL\Scalar]
#[GQL\Description('Timefull scalar type')]
class Timefull
{
    public const FORMAT_WITH_SECONDS = 'H:i:s';

    public static function serialize($value)
    {
        if (!$value instanceof \DateTime) {
            throw new InvariantViolation('DateTime is not an instance of DateTimeImmutable: '.Utils::printSafe($value));
        }

        return $value->format(self::FORMAT_WITH_SECONDS);
    }

    public static function parseValue($value): ?\DateTime
    {
        return \DateTime::createFromFormat(self::FORMAT_WITH_SECONDS, $value) ?: null;
    }

    public static function parseLiteral($ast): ?string
    {
        if ($ast instanceof StringValueNode) {
            return $ast->value;
        }

        return null;
    }
}
