<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Builder;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class CrudBuilderConfiguration implements ConfigurationInterface
{
    public const GET            = 'get';
    public const LIST           = 'list';
    public const CREATE         = 'create';
    public const UPDATE         = 'update';
    public const DELETE         = 'delete';
    public const ALL_OPERATIONS = [self::GET, self::LIST, self::CREATE, self::UPDATE, self::DELETE];

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('builder');
        $rootNode    = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                // default
                ->append($this->addOperationsConfig())

                // types
                ->arrayNode('types')->useAttributeAsKey('types')->prototype('array')
                    ->validate()
                        ->always(function ($v) {
                            if (null !== $v['access'] && null !== $v['permission']) {
                                throw new \InvalidArgumentException('Cannot use both "access" and "permission" keys on same level.');
                            }

                            return $v;
                        })
                    ->end()
                    ->children()
                        ->scalarNode('permission')->defaultNull()->end()
                        ->scalarNode('access')->defaultNull()->end()
                        ->scalarNode('public')->defaultNull()->end()
                        ->scalarNode('entity_id')->defaultTrue()->end()
                            ->variableNode('operations')
                                ->validate()
                                    ->always(function ($v) {
                                        if (\is_string($v) && 'all' !== $v) {
                                            throw new \InvalidArgumentException('Only "all" is supported for "operations" key.');
                                        }
                                        if (\is_array($v)) {
                                            foreach ($v as $value) {
                                                if (!\in_array($value, self::ALL_OPERATIONS)) {
                                                    throw new \InvalidArgumentException(sprintf('Invalid value "%s" for "operations" key in configuration for type "operations".', $value));
                                                }
                                            }
                                        }

                                        return $v;
                                    })
                                ->end()
                            ->end()
                            ->append($this->addOperationGetConfig())
                            ->append($this->addOperationListConfig())
                            ->append($this->addOperationCreateConfig())
                            ->append($this->addOperationUpdateConfig())
                            ->append($this->addOperationDeleteConfig())
                        ->end()
                    ->end()
            ->end()
        ->end()
        ;

        return $treeBuilder;
    }

    protected function addOperationsConfig()
    {
        $treeBuilder = new TreeBuilder('default');
        $rootNode    = $treeBuilder->getRootNode();

        $rootNode
        ->addDefaultsIfNotSet()
        ->children()
                ->append($this->addOperationGetConfig())
                ->append($this->addOperationListConfig())
                ->append($this->addOperationCreateConfig())
                ->append($this->addOperationUpdateConfig())
                ->append($this->addOperationDeleteConfig())
            ->end();

        return $this->addAccessConfig($rootNode);
    }

    public function addAccessConfig(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->validate()
                ->always(function ($v) {
                    if (null !== $v['access'] && null !== $v['permission']) {
                        throw new \InvalidArgumentException('Cannot use both "access" and "permission" keys on same level.');
                    }

                    return $v;
                })
            ->end()
            ->children()
                ->scalarNode('public')->defaultNull()->end()
                ->scalarNode('permission')->defaultNull()->end()
                ->scalarNode('access')->defaultNull()->end()
                ->scalarNode('name')->defaultNull()->end()
            ->end();

        return $rootNode;
    }

    public function addOperationGetConfig()
    {
        $treeBuilder = new TreeBuilder(self::GET);
        $rootNode    = $treeBuilder->getRootNode();

        $rootNode = $this->addAccessConfig($rootNode);

        return $rootNode;
    }

    public function addOperationListConfig()
    {
        $treeBuilder = new TreeBuilder(self::LIST);
        $rootNode    = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('orderBy')->ignoreExtraKeys(false)->end()
                ->arrayNode('criterias')->ignoreExtraKeys(false)->end()
            ->end();

        $rootNode = $this->addAccessConfig($rootNode);

        return $rootNode;
    }

    public function addOperationCreateConfig()
    {
        $treeBuilder = new TreeBuilder(self::CREATE);
        $rootNode    = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('parent')
                    ->children()
                        ->scalarNode('type')->isRequired(true)->end()
                        ->scalarNode('method')->isRequired(true)->end()
                    ->end()
                ->end()
            ->end();

        $rootNode = $this->addAccessConfig($rootNode);

        return $rootNode;
    }

    public function addOperationUpdateConfig()
    {
        $treeBuilder = new TreeBuilder(self::UPDATE);
        $rootNode    = $treeBuilder->getRootNode();

        $rootNode = $this->addAccessConfig($rootNode);

        return $rootNode;
    }

    public function addOperationDeleteConfig()
    {
        $treeBuilder = new TreeBuilder(self::DELETE);
        $rootNode    = $treeBuilder->getRootNode();

        $rootNode = $this->addAccessConfig($rootNode);

        return $rootNode;
    }
}
