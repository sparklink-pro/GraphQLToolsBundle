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

    public function populateInput($target, $input, ?Configuration $configuration = null, array $paths = []): void
    {
        if (!$configuration) {
            $configuration = new Configuration();
        }

        $inputProperties = is_array($input) ? $input : get_object_vars($input);
        
        // Loop through all input/array properties
        foreach ($inputProperties as $inputProperty => $value) {
            $currentPath = [...$paths, $inputProperty];
            $path        = implode('.', $currentPath);
            $pathConfiguration = $configuration->get($path);

            // These property should be ignored
            if ($pathConfiguration->isIgnored($target, $inputProperty, $value) || $value instanceof IgnoredValue) {
                continue;
            }

            // The property should be ignored as it is null
            if ($pathConfiguration->isIgnoreNull() && null === $value) {
                continue;
            }

            // The value is an input object or an array containing an input object
            if ($this->isInputObjectOrArray($value)) {
                $this->processInputValue($target, $inputProperty, $value, $configuration, $currentPath);
                continue;
            }

            // It's a scalar property, set the value accordingly
            try {
                $this->setValue($target, $inputProperty, $value, $pathConfiguration);
            } catch (\Exception $e) {
                throw new \Exception("Unable to set property {$inputProperty} in object ".$target::class.' : '.$e->getMessage());
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

    protected function getValue($entity, string $property, ConfigurationPath $pathConfiguration)
    {
        if ($pathConfiguration->getGetter()) {
            return ($pathConfiguration->getGetter())($entity, $property);
        }

        return $this->accessor->getValue($entity, $property);
    }

    protected function setValue($entity, string $property, $value, ConfigurationPath $pathConfiguration)
    {
        if ($pathConfiguration->getSetter()) {
            return ($pathConfiguration->getSetter())($entity, $property, $value);
        }

        return $this->accessor->setValue($entity, $property, $value);
    }

    // Set a value of type input object or an array of input objects
    protected function processInputValue($target, string $property, $inputValue, Configuration $configuration, array $paths = []): void
    {
        $propertyInfo = $this->propertyInfoExtractor->getTypes($target::class, $property)[0] ?? null;
        $path = implode('.', $paths);
        $pathConfiguration = $configuration->get($path);
        $currentValue = $this->getValue($target, $property, $pathConfiguration);

        if (!$propertyInfo) {
            throw new \Exception("Unable to determine property {$property} info on target class ".$target::class);
        }
        $isCollection = $propertyInfo->isCollection();
        $class        = $propertyInfo->getClassName();

        // The target property is a collection
        if ($isCollection) {
            $class = $propertyInfo->getCollectionValueTypes()[0]?->getClassName();
            if (!$class) {
                throw new \Exception("Unable to determine expected property class for property {$property} on  ".$target::class);
            }
            
            if (!\is_array($inputValue)) {
                throw new \Exception("Expected array input to populate collection property {$property} on  ".$target::class);
            }
            $pathId     = implode('.', [...$paths, 'id']);
            $pathIdConfiguration = $configuration->get($pathId);

            // If id is ignored, we enforce the creation of a new target
            $disableUpdate = $pathIdConfiguration->isIgnored($target, 'id', $inputValue);

            // Create a new collection
            $collection = [];
            foreach ($inputValue as $index => $inputValueEntry) {
                $inputEntryId    = $this->accessor->getValue($inputValueEntry, 'id');
                $entryValue = null;

                // Id is not ignored and we have an id in the input
                // Look for the matching element by id
                if (!$disableUpdate && $inputEntryId) {
                    if (!$currentValue) {
                        throw new \Exception('Unable to find related target');
                    }

                    foreach ($currentValue as $existingEntry) {
                        $existingEntryId = $this->getValue($existingEntry, 'id', $pathIdConfiguration);
                        if ($existingEntryId === $inputEntryId) {
                            $entryValue = $existingEntry;
                            break;
                        }
                    }
                    // Doesn't seem it's part of the existing collection
                    if (!$entryValue) {
                        throw new \Exception("While looking to populate collection property {$property} on target class ".$target::class." the {$class} with id {$inputEntryId} was not found");
                    }
                } else {
                    $entryValue = new $class();
                }
                // Ignore id
                $childConfiguration = clone $configuration;
                $childConfiguration->get($pathId)->setIgnored(true);

                if ($entryValue instanceof RankableEntityInterface) {
                    $entryValue->setRank($index + 1);
                }
                $this->populateInput($entryValue, $inputValueEntry, $childConfiguration, $paths);
                $collection[] = $entryValue;
            }

            $this->setValue($target, $property, $collection, $pathConfiguration);
        } else {
            if (!$currentValue) {
                $currentValue = new $class();
                $this->setValue($target, $property, $currentValue, $pathConfiguration);
            }
            $this->populateInput($currentValue, $inputValue, $configuration, $paths);
        }
    }
}
