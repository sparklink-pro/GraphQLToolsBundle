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
        $typeConfig    = $builderConfig['types'][$type] ?? [];
        $defaultConfig = $builderConfig['default'];

        // type.operation
        if (\array_key_exists($operation, $typeConfig)) {
            if (null !== $typeConfig[$operation]['access'] || null !== $typeConfig[$operation]['permission']) {
                return $this->configureAccess($typeConfig[$operation]);
            }
        }

        // type.all
        if (null !== $typeConfig['access'] || null !== $typeConfig['permission']) {
            return $this->configureAccess($typeConfig);
        }

        // default.operation
        if (\array_key_exists($operation, $defaultConfig)) {
            if (null !== $defaultConfig[$operation]['access'] || null !== $defaultConfig[$operation]['permission']) {
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
        if (\array_key_exists($operation, $typeConfig) && null !== $typeConfig[$operation]['public']) {
            return ['public' => $typeConfig[$operation]['public']];
        }

        // type.all
        if (null !== $typeConfig['public']) {
            return ['public' => $typeConfig['public']];
        }

        // default.operation
        if (\array_key_exists($operation, $defaultConfig)) {
            if (null !== $defaultConfig[$operation]['public']) {
                return ['public' => $defaultConfig[$operation]['public']];
            }
        }

        // default.all
        if (null !== $defaultConfig['public']) {
            return ['public' => $defaultConfig['public']];
        }

        return [];
    }

    protected function getNameOperation(array $builderConfig, string $type, string $operation): string
    {
        $nameOperation = sprintf('%s%s', $type, ucfirst('get' !== $operation ? $operation : ''));

        $typeConfig = $builderConfig['types'][$type];
        if (\array_key_exists($operation, $typeConfig) && null !== $typeConfig[$operation]['name']) {
            return $this->replaceName($typeConfig[$operation]['name'], $type);
        }

        $defaultConfig = $builderConfig['default'];
        if (\array_key_exists($operation, $defaultConfig) && null !== $defaultConfig[$operation]['name']) {
            return $this->replaceName($defaultConfig[$operation]['name'], $type);
        }

        return $nameOperation;
    }

    private function replaceName(?string $name, ?string $type): string
    {
        $name = str_replace('<Type>', $type, $name);

        return $name;
    }

    private function configureAccess($config)
    {
        if (null !== $config['access']) {
            return ['access' => $config['access']];
        }

        if (null !== $config['permission']) {
            return ['access' => sprintf("@=hasRole('%s')", $config['permission'])];
        }

        return [];
    }
}
