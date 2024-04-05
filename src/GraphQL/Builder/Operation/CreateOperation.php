<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Builder\Operation;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class CreateOperation extends Operation
{
    protected function getType(): string
    {
        return $this->type;
    }

    protected function getDescription(): string
    {
        return sprintf("Create a %s", $this->type);
    }

    public function getOperationType(): OperationType
    {
        return OperationType::MUTATION;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('create');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->arrayNode('parent')
                    ->children()
                        ->scalarNode('type')->isRequired(true)->end()
                        ->scalarNode('method')->isRequired(true)->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

    protected function getArgs(): array
    {
        $args = [
            'input' => [
                'type' => sprintf('%s!', $this->getInputType())
            ]
        ];

        if (isset($this->options['parent'])) {
            $args['parent'] = [
                'type' => $this->options['parent']
            ];
        }

        return $args;
    }

    protected function getResolverArguments(array $arguments = [], bool $wrapped = false): string
    {
        $arguments['input'] = $this->getInputType();

        return parent::getResolverArguments($arguments, true);
    }
} 