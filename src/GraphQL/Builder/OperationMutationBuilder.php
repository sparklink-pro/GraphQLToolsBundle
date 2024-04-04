<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Builder;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;
use Sparklink\GraphQLToolsBundle\GraphQL\Builder\Operation\OperationType;

class OperationMutationBuilder extends OperationBuilder implements MappingInterface
{
    public function toMappingDefinition(array $builderConfig): array
    {
        return $this->getMapping($builderConfig, OperationType::MUTATION);
    }
}