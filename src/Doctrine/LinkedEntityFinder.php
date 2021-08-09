<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\Doctrine;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\PropertyAccess\PropertyAccessorBuilder;

class LinkedEntityFinder
{
    protected ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Get a list of entities referencing target entity.
     */
    public function getLinkedEntities(object $entity): array
    {
        $manager    = $this->doctrine->getManager();
        $metas      = $manager->getMetadataFactory()->getAllMetadata();

        $entities   = [];

        foreach ($metas as $meta) {
            foreach ($meta->getAssociationNames() as $association) {
                $target    = $meta->getAssociationTargetClass($association);
                $mapping   = $meta->getAssociationMapping($association);
                $className = $meta->getReflectionClass()->getName();

                if ($target === \get_class($entity)) {
                    $fieldName       = $mapping['fieldName'];
                    $isOwningSide    = $mapping['isOwningSide'];

                    if (!$isOwningSide) {
                        continue;
                    }

                    $isMultiple = ClassMetadataInfo::MANY_TO_MANY === $mapping['type'];

                    $qb = $manager->getRepository($className)->createQueryBuilder('o');
                    $f  = sprintf('%s.%s', 'o', $fieldName);

                    if ($isMultiple) {
                        $qb
                            ->innerJoin($f, 'rel')
                            ->where('rel = :entity');
                    } else {
                        $qb->where($f.' = :entity');
                    }
                    $qb->setParameter('entity', $entity);
                    foreach ($qb->getQuery()->getResult() as $linkedEntity) {
                        $entities[] = [
                            'entity'    => $linkedEntity,
                            'field'     => $fieldName,
                            'multiple'  => $isMultiple,
                            'className' => $className,
                        ];
                    }
                }
            }
        }

        return $entities;
    }

    /** Remove reference to given entity in linked entities */
    public function unlinkEntities(object $entity)
    {
        $links    = $this->getLinkedEntities($entity);
        $accessor = (new PropertyAccessorBuilder())->getPropertyAccessor();

        foreach ($links as $link) {
            ['entity' => $linkedEntity, 'field' => $field, 'multiple' => $multiple] = $link;
            if ($multiple) {
                $value = $accessor->getValue($linkedEntity, $field);
                if ($value instanceof Collection) {
                    $value->removeElement($entity);
                } elseif (\is_array($value)) {
                    $value = array_filter($value, fn ($e) => $e !== $entity);
                }
                $accessor->setValue($linkedEntity, $field, $value);
            } else {
                $accessor->setValue($linkedEntity, $field, null);
            }
        }
    }
}
