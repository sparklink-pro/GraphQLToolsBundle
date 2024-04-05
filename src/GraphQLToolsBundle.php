<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle;

use Sparklink\GraphQLToolsBundle\DependencyInjection\CompilerPass\TypeManagerPass;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class GraphQLToolsBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
            ->scalarNode('use_v2')->defaultFalse()->end()
            ->end()
        ;
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new TypeManagerPass());
    }

    public function loadExtension(array $config, ContainerConfigurator $containerConfigurator, ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->setParameter('graph_ql_tools.use_v2', $config['use_v2']);

        $containerConfigurator->import('./Resources/config/services.yaml');
    }
}
