<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Scalar;

use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Utils\Utils;
use Overblog\GraphQLBundle\Annotation as GQL;

#[GQL\Scalar]
#[GQL\Description('DateTime with Timezone scalar type')]
class DateTimeTz
{
    public static function serialize($value)
    {
        if (!$value instanceof \DateTime) {
            throw new InvariantViolation('DateTime is not an instance of DateTimeImmutable: '.Utils::printSafe($value));
        }

        return $value->format(\DateTime::ATOM);
    }

    public static function parseValue($value): ?\DateTime
    {
        return \DateTime::createFromFormat(\DateTime::ATOM, $value) ?: null;
    }

    public static function parseLiteral($ast): ?string
    {
        if ($ast instanceof StringValueNode) {
            return $ast->value;
        }

        return null;
    }
}
