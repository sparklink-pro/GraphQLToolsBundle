<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Builder\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class TypesConfiguration implements ConfigurationInterface
{
    protected array $operations = [];

    public function __construct(array $operations = [])
    {
        $this->operations = $operations;
    }

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('operations');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->useAttributeAsKey('type')
            ->arrayPrototype()
            ->beforeNormalization()
            ->always(function (array $v): array {
                $operations = $v['operations'] ?? [];

                if (\is_string($operations)) {
                    $operations = [$operations];
                }

                if (\is_array($operations)) {
                    $normalizedOperations = [];
                    foreach ($operations as $key => $value) {
                        /* Convert from the form: xxx: ~ */
                        if (null === $value && \is_string($key)) {
                            $value = $key;
                        }

                        if (\is_string($value)) {
                            if ('all' === $value || '*' === $value) {
                                foreach (array_keys($this->operations) as $operation) {
                                    if (!isset($normalizedOperations[$operation])) {
                                        $normalizedOperations[$operation] = [];
                                    }
                                }
                            } else {
                                $normalizedOperations[$value] = [];
                            }
                        } else {
                            $normalizedOperations[$key] = $value;
                        }
                    }
                    $operations = $normalizedOperations;
                }

                foreach ($operations as $name => $config) {
                    if (!isset($this->operations[$name])) {
                        throw new \InvalidArgumentException(sprintf('Operation "%s" is not supported. Available operations are: "%s"', $name, implode(', ', array_keys($this->operations))));
                    }
                }

                $v['operations'] = $operations;

                return $v;
            })
            ->end()
            ->children()
            ->scalarNode('permission')->end()
            ->scalarNode('access')->end()
            ->scalarNode('public')->end()
            ->arrayNode('operations')
            ->useAttributeAsKey('name')
            ->arrayPrototype()
            ->children()
            ->scalarNode('permission')->end()
            ->scalarNode('access')->end()
            ->scalarNode('public')->end()
            ->arrayNode('options')
            ->ignoreExtraKeys(false)
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->validate()
            ->always(function (array $v) {
                foreach ($v['operations'] as $name => $configuration) {
                    $typeConfiguration = $v;
                    unset($typeConfiguration['operations']);

                    $mergedConfiguration = array_merge(
                        $this->operations[$name],   // Default operations configuration as defined
                        $typeConfiguration,         // Type configuration
                        $configuration,             // Operation configuration
                    );

                    if (null !== $mergedConfiguration['access'] && null !== $mergedConfiguration['permission']) {
                        throw new \InvalidArgumentException(sprintf('Cannot use both "access" and "permission" keys on same level. Access is set to "%s" and permission to "%s". Try unsetting one of them.', $mergedConfiguration['access'], $mergedConfiguration['permission']));
                    }

                    $v['operations'][$name] = $mergedConfiguration;
                }

                return $v;
            })
            ->end()
        ;

        return $treeBuilder;
    }
}
