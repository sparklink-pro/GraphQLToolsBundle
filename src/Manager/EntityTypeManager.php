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

class EntityTypeManager implements EntityTypeManagerInterface
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

    public function get(object $object, array $args = [], array $options = [], ?ResolveInfo $info = null): object
    {
        return $object;
    }

    public function list(array $args = [], array $options = [], ?ResolveInfo $info = null): array
    {
        $criterias = $options['criterias'] ?? [];
        $orderBy = $options['orderBy'] ?? [];

        $items = $this->getRepository()->findBy($criterias, $orderBy);

        return ['items' => $items];
    }

    protected function getInstance($input, $entity = null, ?Configuration $configuration = null)
    {
        if (!$entity) {
            $entity = $this->getEntityInstance();
        }

        $this->populator->populateInput($entity, $input, $configuration);
        $errors = $this->validator->validate($entity);

        if (\count($errors) > 0) {
            throw new InvalidArgumentsError([new InvalidArgumentError('errors', $errors)]);
        }

        return $entity;
    }

    public function update(object $object, object $input, array $args = [], array $options = [], ?ResolveInfo $info = null, ?Configuration $configuration = null): object
    {
        $entity = $this->getInstance($input, $object, $configuration);

        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->refresh($entity);

        return $entity;
    }

    public function create(object $input, array $args = [], array $options = [], ?ResolveInfo $info = null, ?Configuration $configuration = null): object
    {
        $entity = $this->getInstance($input, null, $configuration);
        if (isset($options['parent'])) {
            $args['parent']->{$options['method']}($entity);
        }

        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->refresh($entity);

        return $entity;
    }

    public function delete(object $object, array $args = [], array $options = [], ?ResolveInfo $info = null): bool
    {
        try {
            $this->getEntityManager()->remove($object);
            $this->linksFinder->unlinkEntities($object);
            $this->getEntityManager()->flush();
        } catch (\Exception $e) {
            throw new UserError($e->getMessage());
        }

        return true;
    }
}
