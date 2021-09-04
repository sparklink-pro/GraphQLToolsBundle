<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Builder;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;

abstract class CrudBuilder implements MappingInterface
{
    protected function getEntityIdType(string $type): string
    {
        return sprintf('%sId', $type);
    }
}
