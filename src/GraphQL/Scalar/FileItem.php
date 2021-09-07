<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Scalar;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use GraphQL\Error\InvariantViolation;
use Overblog\GraphQLBundle\Upload\Type\GraphQLUploadType;
use Symfony\Component\HttpFoundation\File\File;

abstract class FileItem extends GraphQLUploadType
{
    protected EntityRepository $repository;

    public function __construct(ManagerRegistry $registry)
    {
        $this->repository = $registry->getRepository($this->getFileEntityClass());
    }

    abstract protected function getFileEntityClass(): string;

    /**
     * {@inheritdoc}
     */
    public function parseValue($value)
    {
        if ($value instanceof File) {
            return $this->createEntityFromFile($value);
        }

        return $this->getEntityFromValue($value);
    }

    /**
     * Create the file entity from uploaded file
     * @param Symfony\Component\HttpFoundation\File\File $file
     * @return null|object
     */
    protected function createEntityFromFile(File $file): ?object
    {
        $class = $this->getFileEntityClass();

        return new $class($file);
    }

    /**
     * Retrieve corresponding file entity from the value
     * @param mixed $value
     * @return null|object
     */
    protected function getEntityFromValue($value): ?object
    {
        if (\is_array($value) && (isset($value['id']) || isset($value['uid']))) {
            $entityId = $value['id'] ?? $value['uid'];

            return $this->repository->find($entityId);
        }
        throw new InvariantViolation('File Item must be a file array or uploaded file item must be a string');
    }
}
