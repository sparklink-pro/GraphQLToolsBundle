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
        $typeConfig    = $builderConfig['types'][$type];
        $defaultConfig = $builderConfig['default'];

        // type.operation
        if (\array_key_exists($operation, $typeConfig)) {
            if (\array_key_exists('access', $typeConfig[$operation]) || \array_key_exists('permission', $typeConfig[$operation])) {
                return $this->configureAccess($typeConfig[$operation]);
            }
        }

        // type.all
        if (\array_key_exists('access', $typeConfig) || \array_key_exists('permission', $typeConfig)) {
            return $this->configureAccess($typeConfig);
        }

        // default.operation
        if (\array_key_exists($operation, $defaultConfig)) {
            if (\array_key_exists('access', $defaultConfig[$operation]) || \array_key_exists('permission', $defaultConfig[$operation])) {
                return $this->configureAccess($defaultConfig[$operation]);
            }
        }

        // default.all
        return $this->configureAccess($builderConfig['default']);
    }

    protected function getPublic(array $builderConfig, string $type, string $operation): array
    {
        $typeConfig    = $builderConfig['types'][$type];
        $defaultConfig = $builderConfig['default'];

        // type.operation
        if (\array_key_exists($operation, $typeConfig)) {
            if (\array_key_exists('public', $typeConfig[$operation])) {
                return ['public' => $typeConfig[$operation]['public']];
            }
        }

        // type.all
        if (\array_key_exists('public', $typeConfig)) {
            return ['public' => $typeConfig['public']];
        }

        // default.operation
        if (\array_key_exists($operation, $defaultConfig)) {
            if (\array_key_exists('public', $defaultConfig[$operation])) {
                return ['public' => $defaultConfig[$operation]['public']];
            }
        }

        // default.all
        if (\array_key_exists('public', $defaultConfig)) {
            return ['public' => $defaultConfig['public']];
        }

        return [];
    }

    protected function getNameOperation(array $builderConfig, string $type, string $operation): string
    {
        $nameOperation = sprintf('%s%s', $type, ucfirst('get' !== $operation ? $operation : ''));

        $typeConfig = $builderConfig['types'][$type];
        if (\array_key_exists($operation, $typeConfig)) {
            if (\array_key_exists('name', $typeConfig[$operation])) {
                return $this->replaceName($typeConfig[$operation]['name'], $type);
            }
        }

        $defaultConfig = $builderConfig['default'];
        if (\array_key_exists($operation, $defaultConfig)) {
            if (\array_key_exists('name', $defaultConfig[$operation])) {
                return $this->replaceName($defaultConfig[$operation]['name'], $type);
            }
        }

        return $nameOperation;
    }

    private function replaceName(string $name, $type): string
    {
        $name = str_replace('<Type>', $type, $name);

        return $name;
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
