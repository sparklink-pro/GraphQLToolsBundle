<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Builder\Operation;

class DeleteOperation extends Operation
{
    public function getOperationType(): OperationType
    {
        return OperationType::MUTATION;
    }

    protected function getDescription(): string
    {
        return sprintf('Delete a %s', $this->type);
    }

    public function getType(): string
    {
        return 'Boolean!';
    }

    public function getArgs(): array
    {
        return ['item' => $this->getScalarIdType()];
    }

    protected function getResolverArguments(array $arguments = [], bool $wrapped = false): string
    {
        return parent::getResolverArguments($this->getArgs(), false);
    }
}
