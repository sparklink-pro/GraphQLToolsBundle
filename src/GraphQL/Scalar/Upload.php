<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Scalar;

use GraphQL\Error\InvariantViolation;
use Overblog\GraphQLBundle\Annotation as GQL;
use Overblog\GraphQLBundle\Upload\Type\GraphQLUploadType;
use Symfony\Component\HttpFoundation\File\File;

#[GQL\Scalar(scalarType: '@=newObject("Overblog\\\GraphQLBundle\\\Upload\\\Type\\\GraphQLUploadType")')]
#[GQL\Description('Upload scalar type')]
class Upload extends GraphQLUploadType
{
    /**
     * {@inheritdoc}
     */
    public function parseValue($value)
    {
        if (null !== $value && !$value instanceof File) {
            throw new InvariantViolation(\sprintf('Upload should be null or instance of "%s" but %s given.', File::class, \is_object($value) ? \get_class($value) : \gettype($value)));
        }


        return $value;
    }
}
