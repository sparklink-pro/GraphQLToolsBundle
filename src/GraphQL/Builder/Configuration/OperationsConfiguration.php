<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Builder\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Sparklink\GraphQLToolsBundle\GraphQL\Builder\Operation\CreateOperation;
use Sparklink\GraphQLToolsBundle\GraphQL\Builder\Operation\DeleteOperation;
use Sparklink\GraphQLToolsBundle\GraphQL\Builder\Operation\GetOperation;
use Sparklink\GraphQLToolsBundle\GraphQL\Builder\Operation\IdOperation;
use Sparklink\GraphQLToolsBundle\GraphQL\Builder\Operation\ListOperation;
use Sparklink\GraphQLToolsBundle\GraphQL\Builder\Operation\OperationInterface;
use Sparklink\GraphQLToolsBundle\GraphQL\Builder\Operation\UpdateOperation;

class OperationsConfiguration implements ConfigurationInterface
{
    public const GET    = 'get';
    public const LIST   = 'list';
    public const CREATE = 'create';
    public const UPDATE = 'update';
    public const DELETE = 'delete';
    public const ID     = 'id';

    public const DEFAULT_OPERATIONS = [
        self::GET    => GetOperation::class, 
        self::LIST   => ListOperation::class, 
        self::CREATE => CreateOperation::class, 
        self::UPDATE => UpdateOperation::class, 
        self::DELETE => DeleteOperation::class,
        self::ID     => IdOperation::class
    ];

    protected array $defaults = [];

    public function __construct(array $defaults = [])
    {
        $this->defaults = $defaults;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('operations');
        $rootNode    = $treeBuilder->getRootNode();

        $rootNode
            ->beforeNormalization()
                ->always()
                ->then(function (array $v): array { 
                    return array_merge_recursive(array_map(fn($operation) => ['class' => $operation], self::DEFAULT_OPERATIONS), $v);
                })
            ->end()
            ->useAttributeAsKey('name')
            ->arrayPrototype()
                ->children()
                    ->scalarNode('class')->isRequired()->end()
                    ->scalarNode('permission')->defaultValue($this->defaults['permission'])->end()
                    ->scalarNode('access')->defaultValue($this->defaults['access'])->end()
                    ->scalarNode('public')->defaultValue($this->defaults['public'])->end()
                    ->arrayNode('options')
                        ->ignoreExtraKeys(false)
                    ->end()
                ->end()
                ->validate()
                    ->always(function (array $v): array {
                        if (!class_exists($v['class'])) {
                            throw new \InvalidArgumentException(sprintf('Operation class "%s" does not exist.', $v['class']));
                        }
                        
                        $reflection = new \ReflectionClass($v['class']);
                        if (!$reflection->implementsInterface(OperationInterface::class)) {
                            throw new \InvalidArgumentException(sprintf('Operation class "%s" must implement "%s".', $v['class'], OperationInterface::class));
                        }

                        return $v;
                    })
            ->end()
        ;

        return $treeBuilder;
    }
}
