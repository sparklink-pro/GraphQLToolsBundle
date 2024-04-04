<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Builder\Operation;

class UpdateOperation extends CreateOperation
{
    protected function getDescription(): string
    {
        return sprintf('Update a %s', $this->type);
    }

    protected function getArgs(): array
    {
        return [
            'item' => $this->getScalarIdType(),
        ] + parent::getArgs();
    }

    protected function getResolverArguments(array $arguments = [], bool $wrapped = false): string
    {
        return parent::getResolverArguments(['item' => $this->getScalarIdType()], true);
    }
}
