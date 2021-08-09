<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Scalar;

use App\Entity\File as EntityFile;
use App\Repository\FileRepository;
use Doctrine\Persistence\ManagerRegistry;
use GraphQL\Error\InvariantViolation;
use Overblog\GraphQLBundle\Annotation as GQL;
use Overblog\GraphQLBundle\Upload\Type\GraphQLUploadType;
use Symfony\Component\HttpFoundation\File\File;

#[GQL\Scalar(name: 'FileItem', scalarType: "@=newObject('Sparklink\\\GraphQLToolsBundle\\\GraphQL\\\Scalar\\\FileItem', [service('doctrine')])")]
#[GQL\Description('File Item scalar type')]
class FileItem extends GraphQLUploadType
{
    protected FileRepository $repository;

    public function __construct(ManagerRegistry $registry)
    {
        $this->repository = $registry->getRepository(EntityFile::class);
    }

    /**
     * {@inheritdoc}
     */
    public function parseValue($file)
    {
        if ($file instanceof File) {
            return new EntityFile($file);
        } elseif (\is_array($file) && 'File' === $file['__typename'] && $file['id']) {
            return $this->repository->find($file['id']);
        } else {
            throw new InvariantViolation('File Item must be a file array or uploaded file item must be a string');
        }
    }
}
