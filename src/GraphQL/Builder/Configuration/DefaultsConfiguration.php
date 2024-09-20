<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Builder\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class DefaultsConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('defaults');
        $rootNode    = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->scalarNode('permission')->defaultNull()->end()
                ->scalarNode('access')->defaultNull()->end()
                ->scalarNode('public')->defaultNull()->end()
            ->end()
        ->end()
        ;

        return $treeBuilder;
    }
}
