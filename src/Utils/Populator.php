<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\Utils;

use Sparklink\GraphQLToolsBundle\Entity\Interface\RankableEntityInterface;
use Sparklink\GraphQLToolsBundle\Service\TypeEntityResolver;
use Sparklink\GraphQLToolsBundle\Utils\Populator\IgnoredValue;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;

class Populator
{
    protected PropertyAccessor $accessor;

    public function __construct(protected TypeEntityResolver $entityResolver, protected PropertyInfoExtractorInterface $propertyInfoExtractor)
    {
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    public function populateInput($entity, $input, Configuration $configuration = null, array $paths = []): void
    {
        if (!$configuration) {
            $configuration = new Configuration();
        }

        $inputProperties = get_object_vars($input);
        $ignoredPath     = $configuration->getIgnoredPaths();

        foreach ($inputProperties as $inputProperty => $value) {
            $currentPath = [...$paths, $inputProperty];
            $path        = implode('.', $currentPath);

            // Ignored property
            if (\in_array($path, $ignoredPath) || $value instanceof IgnoredValue) {
                continue;
            }

            if ($this->isInputObjectOrArray($value)) {
                $this->processInputValue($entity, $inputProperty, $value, $configuration, $currentPath);
                continue;
            }

            $ignoreNull = $configuration->getIgnoreNull($path);
            if ($ignoreNull && null === $value) {
                continue;
            }

            try {
                $this->accessor->setValue($entity, $inputProperty, $value);
            } catch (\Exception $e) {
                throw new \Exception("Unable to set property {$inputProperty} in object ".$entity::class.' : '.$e->getMessage());
            }
        }
    }

    /**
     * Is an input object or an array containing an input object.
     */
    protected function isInputObjectOrArray($value): bool
    {
        $object = $value;
        if (\is_array($value)) {
            $object = $value[0] ?? null;
        }

        if (!\is_object($object)) {
            return false;
        }
        $mapping = $this->entityResolver->getMapping($object::class);

        return $mapping && 'input' === $mapping['type'];
    }

    protected function processInputValue($entity, string $property, $inputValue, Configuration $configuration, array $paths = []): void
    {
        $propertyInfo = $this->propertyInfoExtractor->getTypes($entity::class, $property)[0] ?? null;
        $currentValue = $this->accessor->getValue($entity, $property);

        if (!$propertyInfo) {
            throw new \Exception("Unable to determine property {$property} info on entity class ".$entity::class);
        }
        $isCollection = $propertyInfo->isCollection();
        $class        = $propertyInfo->getClassName();

        if ($isCollection) {
            $class = $propertyInfo->getCollectionValueTypes()[0]?->getClassName();
            if (!$class) {
                throw new \Exception("Unable to determine expected property class for property {$property} on  ".$entity::class);
            }
            if (!\is_array($inputValue)) {
                throw new \Exception("Expected array input to populate collection property {$property} on  ".$entity::class);
            }
            $pathId     = implode('.', [...$paths, 'id']);
            $collection = [];

            // If id is ignored, we enforce the creation of a new entity
            $disableUpdate = $configuration->isIgnored($pathId);

            foreach ($inputValue as $index => $inputValueEntry) {
                $entryId    = $this->accessor->getValue($inputValueEntry, 'id');
                $entryValue = null;

                if (!$disableUpdate && $entryId) {
                    if (!$currentValue) {
                        throw new \Exception('Unable to find related entity');
                    }

                    foreach ($currentValue as $existingEntry) {
                        if ($this->accessor->getValue($existingEntry, 'id') === $entryId) {
                            $entryValue = $existingEntry;
                            break;
                        }
                    }
                    if (!$entryValue) {
                        throw new \Exception("While looking to populate collection property {$property} on entity class ".$entity::class." the {$class} with id {$entryId} was not found");
                    }
                } else {
                    $entryValue = new $class();
                }
                // Ignore id
                $childConfiguration = clone $configuration;
                $childConfiguration->ignore($pathId);
                if ($entryValue instanceof RankableEntityInterface) {
                    $entryValue->setRank($index + 1);
                }
                $this->populateInput($entryValue, $inputValueEntry, $childConfiguration, $paths);
                $collection[] = $entryValue;
            }
            $this->accessor->setValue($entity, $property, $collection);
        } else {
            if (!$currentValue) {
                $currentValue = new $class();
                $this->accessor->setValue($entity, $property, $currentValue);
            }
            $this->populateInput($currentValue, $inputValue, $configuration, $paths);
        }
    }
}
