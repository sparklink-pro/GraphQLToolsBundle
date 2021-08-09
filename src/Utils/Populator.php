<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\Utils;

use App\Entity\File;
use App\GraphQL\Scalar\DeletedFile;
use Doctrine\Common\Collections\Collection;
use Sparklink\GraphQLToolsBundle\Service\TypeEntityResolver;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PropertyAccess\PropertyAccess;

class Populator
{
    protected TypeEntityResolver $entityResolver;

    public function __construct(TypeEntityResolver $entityResolver)
    {
        $this->entityResolver = $entityResolver;
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    public function populateInput($entity, $input, Configuration $configuration = null, array $paths = []): void
    {
        if (!$configuration) {
            $configuration = new Configuration();
        }
        $inputProperties = \get_object_vars($input);
        $ignoredPath = $configuration->getIgnoredPaths();
        foreach ($inputProperties as $property => $value) {
            $processed = false;
            $currentPath = [...$paths, $property];
            $path = \join('.', $currentPath);
            if (\in_array($path, $ignoredPath)) {
                continue;
            }
            if (\is_object($value)) {
                $mapping = $this->entityResolver->getMapping(\get_class($value));
                $isInput = $mapping && 'input' === $mapping['type'];
                if ($isInput) {
                    $getter = $configuration->getGetter($path);
                    $target = $getter ? $getter($entity) : $this->accessor->getValue($entity, $property);
                    if ($target) {
                        $this->populateInput($target, $value, $configuration, $currentPath);
                        $processed = true;
                    }
                }
            }
            if (!$processed) {
                $ignoreNull = $configuration->getIgnoreNull($path);
                if ($ignoreNull && null === $value) {
                    continue;
                }
                $setter = $configuration->getSetter($path);
                if ($setter) {
                    $setter($entity, $value);
                } else {
                    $this->setScalarValue($entity, $property, $value);
                }
            }
        }
    }

    public function mergeCollections(Collection $collection, array $inputCollection = null): int
    {
        $result = 0;
        if (!$inputCollection) {
            $result = $collection->count();
            $collection->clear();
        } else {
            foreach ($collection as $entry) {
                if (!\in_array($entry, $inputCollection)) {
                    $collection->removeElement($entry);
                    ++$result;
                }
            }
            foreach ($inputCollection as $entry) {
                if (!$collection->contains($entry)) {
                    $collection->add($entry);
                    ++$result;
                }
            }
        }

        return $result;
    }

    protected function setScalarValue($entity, $property, $value): void
    {
        if ($value instanceof UploadedFile) {
            $value = new File($value);
        } elseif ($value instanceof DeletedFile) {
            $value = null;
        }
        $this->accessor->setValue($entity, $property, $value);
    }
}
