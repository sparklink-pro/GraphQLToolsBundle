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

#[GQL\Scalar(name: 'FileCollection', scalarType: "@=newObject('App\\GraphQL\\Scalar\\FileCollection', [service('doctrine')])")]
#[GQL\Description('File Collection scalar type')]
class FileCollection extends GraphQLUploadType
{
    protected FileRepository $repository;

    public function __construct(ManagerRegistry $registry)
    {
        $this->repository = $registry->getRepository(EntityFile::class);
    }

    /**
     * {@inheritdoc}
     */
    public function parseValue($value)
    {
        $files = [];
        if (\is_array($value)) {
            foreach ($value as $file) {
                if ($file instanceof File) {
                    $files[] = new EntityFile($file);
                } elseif (\is_array($file) && 'File' === $file['__typename'] && $file['id']) {
                    $files[] = $this->repository->find($file['id']);
                } else {
                    throw new InvariantViolation(\sprintf('FileCollection item must be a string or a Uploaded file'));
                }
            }
        } else {
            throw new InvariantViolation(\sprintf('FileCollections scalar must be an array of string or Uploaded file'));
        }

        return $files;
    }
}
