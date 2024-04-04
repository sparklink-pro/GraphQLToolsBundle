<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Builder\Operation;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class ListOperation extends Operation
{
    public function getOperationType(): OperationType
    {
        return OperationType::QUERY;
    }

    protected function getDescription(): string
    {
        return sprintf("List all objects of type %s", $this->type);
    }

    protected function getType(): string
    {
        return sprintf("[%s!]!", $this->getPayloadName());
    }

    protected function getPayloadName(): string
    {
        return sprintf("%sPayload", $this->getName());
    }

    protected function getArgs(): array
    {
        return [
            'limit'   => ['type' => 'Int'],
            'offset'  => ['type' => 'Int'],
            'orderBy' => ['type' => '[OrderListInput!]'],
        ];
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('list');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->arrayNode('criterias')->end()
                ->arrayNode('orderBy')->end()
            ->end();

        return $treeBuilder;
    }

    protected function getAdditionalTypes(): array
    {
        return [
            $this->getPayloadName() => [
                'type' => 'object',
                'config' => [
                    'fields' => [
                        'items' => sprintf("[%s!]!", $this->type),
                    ]
                ]
            ]
        ];
    }
}