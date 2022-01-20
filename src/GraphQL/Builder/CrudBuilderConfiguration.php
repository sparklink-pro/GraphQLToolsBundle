<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Builder;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class CrudBuilderConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('builder');
        $rootNode    = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->scalarNode('access')->defaultNull()->end()
                ->scalarNode('permission')->defaultNull()->end()
                ->arrayNode('names')
                    ->children()
                        ->scalarNode('list')->defaultValue('<type>List')->end()
                        ->scalarNode('delete')->defaultValue('Delete<Type>')->end()
                    ->end()
                ->end()

                ->arrayNode('types')->useAttributeAsKey('types')->prototype('array')
                    ->validate()
                            ->always(function ($v) {
                                // check if the tree as children key 'operations'.

                                if (null !== $v['access'] && null !== $v['permission']) {
                                    throw new \InvalidArgumentException('Cannot use both "access" and "permission" keys in configuration for type "operations".');
                                }

                                return $v;
                            })
                    ->end()
                    ->children()
                        ->arrayNode('operations')
                            ->beforeNormalization()->ifString()->castToArray()->end()
                            ->isRequired()
                            ->prototype('scalar')->end()
                            ->validate()
                                ->always(function ($v) {
                                    foreach ($v as $value) {
                                        if ('all' === $value) {
                                            return $v;
                                        }
                                        if (!\in_array($value, ['all', 'get', 'list', 'create', 'update', 'delete'])) {
                                            throw new \InvalidArgumentException(sprintf('Invalid value "%s" for "operations" key in configuration for type "operations".', $value));
                                        }

                                        return $v;
                                    }
                                })
                            ->end()
                        ->end()
                        ->scalarNode('access')->defaultNull()->end()
                        ->scalarNode('permission')->defaultNull()->end()
                        ->arrayNode('list')
                            ->children()
                                ->scalarNode('permission')->defaultNull()->end()
                                ->arrayNode('orderBy')
                                    ->useAttributeAsKey('name')
                                        ->prototype('scalar')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
