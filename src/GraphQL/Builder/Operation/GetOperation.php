<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Builder\Operation;

class GetOperation extends Operation
{
    public function getOperationType(): OperationType
    {
        return OperationType::QUERY;
    }

    /**
     * Get the name of the operation.
     */
    protected function getName(): string
    {
        return $this->type;
    }

    public function getType(): string
    {
        return sprintf('%s!', $this->type);
    }

    public function getArgs(): array
    {
        return ['id' => $this->getScalarIdType()];
    }

    protected function getResolverArguments(array $arguments = [], bool $wrapped = false): string
    {
        return parent::getResolverArguments($this->getArgs());
    }
}
