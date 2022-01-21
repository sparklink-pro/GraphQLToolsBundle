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

        if (!\array_key_exists('operations', $typeConfig)) {
            return false;
        }

        if (\is_string($typeConfig['operations']) && 'all' === $typeConfig['operations']) {
            return true;
        }
        if (\is_array($typeConfig['operations'])) {
            return \in_array($operation, $typeConfig['operations']) || \in_array('all', $typeConfig['operations']);
        }

        throw new Exception('Invalid "operations" key in configuration for type "'.$type.'".');
    }

    protected function getAccess(array $builderConfig, string $type, string $operation): array
    {
        $typeConfig = $builderConfig['types'][$type];
        if (\array_key_exists($operation, $typeConfig)) {
            if (\array_key_exists('access', $typeConfig[$operation]) || \array_key_exists('permission', $typeConfig[$operation])) {
                return $this->configureAccess($typeConfig[$operation]);
            }
        }

        if (\array_key_exists('access', $typeConfig) || \array_key_exists('permission', $typeConfig)) {
            return $this->configureAccess($typeConfig);
        }

        $defaultConfig = $builderConfig['default'];
        if (\array_key_exists($operation, $defaultConfig)) {
            if (\array_key_exists('access', $defaultConfig[$operation]) || \array_key_exists('permission', $defaultConfig[$operation])) {
                return $this->configureAccess($defaultConfig[$operation]);
            }
        }

        return $this->configureAccess($builderConfig['default']);
    }

    private function configureAccess($config)
    {
        if (\array_key_exists('access', $config)) {
            $access = ['access' => $config['access']];

            return $access;
        }

        if (\array_key_exists('permission', $config)) {
            $access = ['access' => sprintf("@=hasRole('%s')", $config['permission'])];

            return $access;
        }

        return [];
    }

            }
            if (\array_key_exists('permission', $defaultConfig[$operation])) {
                $access = ['access' => sprintf("@=hasRole('%s')", $defaultConfig[$operation]['permission'])];

                return $access;
            }
        }

        return [];
    }
}
