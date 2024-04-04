<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Builder\Operation;

class IdOperation extends Operation
{
    public function getOperationType(): OperationType
    {
        return OperationType::NONE;
    }

    protected function getType(): string
    {
        return $this->getScalarIdType();
    }

    protected function getAdditionalTypes(): array
    {
        $resolver = 'graphql_resolver';
        $type = $this->getScalarIdType();

        return [
            $type => [
                'type' => 'custom-scalar',
                'config' => [
                    'scalarType' => sprintf('@=newObject("Sparklink\\\GraphQLToolsBundle\\\GraphQL\\\Doctrine\\\EntityIdType", [service("doctrine"), service("%s").getEntity("%s")])', $resolver, $this->type),
                ],
            ],
        ];
    }
}
