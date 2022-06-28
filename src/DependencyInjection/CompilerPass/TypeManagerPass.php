<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TypeManagerPass implements CompilerPassInterface
{
    public const TAG = 'sparklink.type_manager';

    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds(self::TAG) as $typeManagerServiceId => $tags) {
            $definition = $container->getDefinition($typeManagerServiceId);
            $tag = $definition->getTag(self::TAG)[0];

            if (!isset($tag['type']) || !\is_string($tag['type'])) {
                throw new \RuntimeException(sprintf('Type manager "%s" defined with tag "%s" must have "type" string setting', $typeManagerServiceId, self::TAG));
            }

            $definition->addMethodCall('setType', [$tag['type']]);
        }
    }
}
