<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Resolver;

use Overblog\GraphQLBundle\Resolver\TypeResolver;

class DefaultInterfaceResolver
{
    private TypeResolver $typeResolver;

    public function __construct(TypeResolver $typeResolver)
    {
        $this->typeResolver = $typeResolver;
    }

    public function resolveType($value)
    {
        $className = \get_class($value);
        $className = str_replace('App\Entity\\', '', $className);

        return $this->typeResolver->resolve($className);
    }
}
