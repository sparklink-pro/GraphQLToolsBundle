<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Builder\Operation;

class GetOperation extends Operation
{
    public function getOperationType(): OperationType
    {
        return OperationType::QUERY;
    }
    
    public function getType(): string
    {
        return sprintf("%s!", $this->type);
    }

    public function getArgs(): array
    {
        return [$this->getArgType() => $this->getScalarIdType()];
    }

    protected function getResolverArguments(array $arguments = [], bool $wrapped = false): string
    {
        return parent::getResolverArguments($this->getArgs());
    }
}