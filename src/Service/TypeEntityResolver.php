<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\Service;

class TypeEntityResolver
{
    protected array $mapping;

    public function __construct(array $mapping)
    {
        $this->mapping = $mapping;
    }

    public function getEntity(string $gqlType): string
    {
        if (!isset($this->mapping[$gqlType])) {
            throw new \InvalidArgumentException(sprintf('The GraphQL resolver could not find the entity for the GraphQL Type "%s"', $gqlType));
        }

        return $this->mapping[$gqlType]['class'];
    }

    public function getType(string $entity): string
    {
        foreach ($this->mapping as $type => $details) {
            if (isset($details['class']) && $details['class'] == $entity) {
                return $type;
            }
        }

        throw new \InvalidArgumentException(sprintf('The GraphQL resolver could not find the GraphQL Type for the entity "%s"', $entity));
    }

    public function getMapping(?string $className = null)
    {
        if (!$className) {
            return $this->mapping;
        }

        foreach ($this->mapping as $type => $details) {
            if (isset($details['class']) && $details['class'] === $className) {
                return $details;
            }
        }

        return false;
    }
}
