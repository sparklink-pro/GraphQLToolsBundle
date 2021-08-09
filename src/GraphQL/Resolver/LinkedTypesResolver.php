<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Resolver;

use Sparklink\GraphQLToolsBundle\Doctrine\LinkedEntityFinder;
use Sparklink\GraphQLToolsBundle\Entity\Interface\TypeStringableInterface;
use Sparklink\GraphQLToolsBundle\Service\TypeEntityResolver;

/**
 * Return an array of linked types for a given type.
 */
class LinkedTypesResolver
{
    public function __construct(protected LinkedEntityFinder $finder, protected TypeEntityResolver $resolver)
    {
    }

    public function getLinkedTypes(object $entity): array
    {
        $links    = $this->finder->getLinkedEntities($entity);
        $types    = [];

        foreach ($links as $link) {
            ['entity' => $entity, 'className' => $className] = $link;

            $type = $this->resolver->getType($className);
            if (!isset($types[$type])) {
                $types[$type] = [];
            }

            $types[$type][] = $entity instanceof TypeStringableInterface ? $entity->_typeToString() : $entity->__toString();
        }

        return $types;
    }
}
