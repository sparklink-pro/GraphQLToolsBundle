<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Scalar;

use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Utils\Utils;
use Overblog\GraphQLBundle\Annotation as GQL;

#[GQL\Scalar]
#[GQL\Description('Date scalar type')]
class Date
{
    public const FORMAT = 'Y-m-d';

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

        if (!$date) {
            try {
                $date = new \DateTime($value);
            } catch (\Exception $e) {
                return null;
            }
        }

        $date->setTime(0, 0);

        return $date;
    }

    public static function parseLiteral($ast): ?string
    {
        if ($ast instanceof StringValueNode) {
            return $ast->value;
        }

        return null;
    }
}
