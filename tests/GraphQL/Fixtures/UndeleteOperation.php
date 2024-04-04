<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\Tests\GraphQL\Fixtures;

use Sparklink\GraphQLToolsBundle\GraphQL\Builder\Operation\Operation;
use Sparklink\GraphQLToolsBundle\GraphQL\Builder\Operation\OperationType;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class UndeleteOperation extends Operation
{
    public function getType(): string
    {
        return $this->type;
    }

    public function getOperationType(): OperationType
    {
        return OperationType::MUTATION;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('operation');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->scalarNode('option1')->end()
                ->arrayNode('option2')
                    ->scalarPrototype()->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
