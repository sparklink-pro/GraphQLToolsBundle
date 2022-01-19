<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Types;

use GraphQL\Error\InvariantViolation;
use GraphQL\Type\Definition\ScalarType;
use PHPUnit\TextUI\XmlConfiguration\File;

class UploadType extends ScalarType
{
    /**
     * @param string $name
     */
    public function __construct(string $name = null)
    {
        parent::__construct([
            'name'        => $name,
            'description' => sprintf(
                'The `%s` scalar type represents a file upload object that resolves an object containing `stream`, `filename`, `mimetype` and `encoding`.',
                $name
            ),
        ]);
    }

    public function parseValue($value): mixed
    {
        if (null !== $value && !$value instanceof File) {
            throw new InvariantViolation(sprintf('Upload should be null or instance of "%s" but %s given.', File::class, \is_object($value) ? \get_class($value) : \gettype($value)));
        }

        return $value;
    }

    public function serialize($value): void
    {
        throw new InvariantViolation(sprintf('%s scalar serialization unsupported.', $this->name));
    }

    public function parseLiteral($valueNode, array $variables = null): void
    {
        throw new InvariantViolation(sprintf('%s scalar literal unsupported.', $this->name));
    }
}
