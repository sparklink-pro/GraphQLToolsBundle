<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\Manager;

use Doctrine\Persistence\ManagerRegistry;
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
        $this->type        = $type;
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

    public function list($config, $orderBy): array
    {
        return ['items' => $this->getRepository()->findBy([], $orderBy)];
    }

    public function item($entity)
    {
        return $entity;
    }

    public function update($entity = null, $input, Configuration $configuration = null): object
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

        $this->getEntityManager()->flush();

        return $entity;
    }

    public function create($input, Configuration $configuration = null): object
    {
        return $this->update(null, $input, $configuration);
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
