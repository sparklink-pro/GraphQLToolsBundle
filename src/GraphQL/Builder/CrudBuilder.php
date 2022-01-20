<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Builder;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Config\Definition\Processor;

abstract class CrudBuilder implements MappingInterface
{
    protected function getConfiguration(array $config)
    {
        $processor              = new Processor();
        $builderConfiguration   = new CrudBuilderConfiguration();

        return $processor->processConfiguration(
            $builderConfiguration,
            [$config]
        );
    }

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

    protected function getAccess(array $builderConfig, string $type): array
    {
        if (\array_key_exists('access', $builderConfig['types'][$type])) {
            $access = ['access' => $builderConfig['types'][$type]['access']];

            return $access;
        }

        if (\array_key_exists('permission', $builderConfig['types'][$type])) {
            $access = ['access' => sprintf("@=hasRole('%s')", $builderConfig['types'][$type]['permission'])];

            return $access;
        }

        if (\array_key_exists('access', $builderConfig)) {
            $access = ['access' => $builderConfig['access']];

            return $access;
        }
        if (\array_key_exists('permission', $builderConfig)) {
            $access = ['access' => sprintf("@=hasRole('%s')", $builderConfig['permission'])];

            return $access;
        }

        return [];
    }
}
