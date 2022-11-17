<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\Extension\Translation\GraphQL\Scalar;

use GraphQL\Error\InvariantViolation;
use GraphQL\Utils\Utils;
use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Scalar
 * @GQL\Description("Translatable string")
 */
#[GQL\Scalar()]
class StringTranslated
{
    public const LOCALES = ['fr', 'en'];

    public static function serialize($value)
    {
        if (!\is_array($value)) {
            throw new InvariantViolation('Translatable String is not an array'.Utils::printSafe($value));
        }

        return $value;
    }

    public static function parseValue($value): ?StringTranslated
    {
        if (!\is_array($value)) {
            throw new InvariantViolation('Translatable string must be an array of string with locale as key');
        }

        foreach ($value as $locale => $text) {
            if ((!\is_string($text) && null !== $text) || !\is_string($locale) || !\in_array($locale, self::LOCALES)) {
                throw new InvariantViolation('Translatable string must be an array of string with locale as key');
            }
        }

        return new StringTranslated($value);
    }

    public static function parseLiteral($ast): ?string
    {
        return null;
    }
}
