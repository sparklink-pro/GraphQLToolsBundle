<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\Manager;

use Doctrine\Persistence\ManagerRegistry;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Error\InvalidArgumentError;
use Overblog\GraphQLBundle\Error\InvalidArgumentsError;
use Overblog\GraphQLBundle\Error\UserError;
use Sparklink\GraphQLToolsBundle\Doctrine\LinkedEntityFinder;
use Sparklink\GraphQLToolsBundle\Service\TypeEntityResolver;
use Sparklink\GraphQLToolsBundle\Utils\Configuration;
use Sparklink\GraphQLToolsBundle\Utils\Populator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DefaultEntityTypeManager implements EntityTypeManagerInterface
{
    protected string $type;
    protected string $entityClass;

    public function __construct(
        protected TypeEntityResolver $resolver,
        protected ManagerRegistry $registry,
        protected ValidatorInterface $validator,
        protected Populator $populator,
        protected LinkedEntityFinder $linksFinder
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    protected function getEntityInstance(): object
    {
        return new $this->entityClass();
    }

    public function setType(string $type): void
    {
        $this->type = $type;
        $this->entityClass = $this->resolver->getEntity($type);
    }

    protected function getEntityManager()
    {
        return $this->registry->getManagerForClass($this->entityClass);
    }

    protected function getRepository()
    {
        return $this->getEntityManager()->getRepository($this->entityClass);
    }

    /**
     * @param $criterias array       List of criterias to filter the result set by the crud query builder from configuration
     * @param $orders    array       List of orderBy to sort the result set by the crud query builder from configuration
     * @param $args      array       List of GraphQL query arguments
     * @param $info      ResolveInfo associated with the query
     */
    public function list(array $criterias = [], array $orderBy = [], array $args = [], ResolveInfo $info = null): array
    {
        return ['items' => $this->getRepository()->findBy($criterias, $orderBy)];
    }

    /**
     * @param $entity object      The resolved entity through the EntityId scalar
     * @param $args   array       List of GraphQL query arguments
     * @param $info   ResolveInfo associated with the query
     */
    public function item(object $entity, array $args = [], ResolveInfo $info = null)
    {
        return $entity;
    }

    protected function getInstance($input, $entity = null, Configuration $configuration = null)
    {
        if (!$entity) {
            $entity = $this->getEntityInstance();
            $this->getEntityManager()->persist($entity);
        }

        $this->populator->populateInput($entity, $input, $configuration);
        $errors = $this->validator->validate($entity);

        if (\count($errors) > 0) {
            throw new InvalidArgumentsError([new InvalidArgumentError('errors', $errors)]);
        }

        return $entity;
    }

    public function update($input, $entity = null, Configuration $configuration = null): object
    {
        $entity = $this->getInstance($input, $entity, $configuration);
        $this->getEntityManager()->flush();

        return $entity;
    }

    public function create($input, $parent = null, string $method = null, Configuration $configuration = null): object
    {
        $entity = $this->getInstance($input, null, $configuration);
        if ($parent) {
            $parent->{$method}($entity);
        }
        $this->getEntityManager()->flush();

        return $entity;
    }

    public function delete($entity): bool
    {
        try {
            $this->getEntityManager()->remove($entity);
            $this->linksFinder->unlinkEntities($entity);
            $this->getEntityManager()->flush();
        } catch (\Exception $e) {
            throw new UserError($e->getMessage());
        }

        return true;
    }
}
