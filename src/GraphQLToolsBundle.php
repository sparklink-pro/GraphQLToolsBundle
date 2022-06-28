<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle;

use Sparklink\GraphQLToolsBundle\DependencyInjection\CompilerPass\TypeManagerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class GraphQLToolsBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new TypeManagerPass());
    }
}
