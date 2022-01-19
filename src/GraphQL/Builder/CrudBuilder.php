<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Builder;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;
use Symfony\Component\Config\Definition\Exception\Exception;

abstract class CrudBuilder implements MappingInterface
{
    protected function getEntityIdType(string $type): string
    {
        return sprintf('%sId', $type);
    }

    protected function isOperationActive(array $builderConfig, string $type, string $operation): bool
    {
        $typeConfig = $builderConfig['types'][$type];

        if (\is_string($typeConfig['operations']) && 'all' === $typeConfig['operations']) {
            return true;
        }
        if (\is_array($typeConfig['operations'])) {
            return \in_array($operation, $typeConfig['operations']) || \in_array('all', $typeConfig['operations']);
        }

        throw new Exception('Invalid "operations" key in configuration for type "'.$type.'".');
    }
}
