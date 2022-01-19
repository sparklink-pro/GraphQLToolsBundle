<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Builder;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;

class CrudEntityIdBuilder extends CrudBuilder implements MappingInterface
{
    public function toMappingDefinition(array $configTypes): array
    {
        $resolver = 'graphql_resolver';
        $types = [];

        foreach ($configTypes as $type => $configuration) {
            if (array_key_exists('entity_id', $configuration) && $configuration['entity_id'] === false) {
                continue;
            }
            $entityIdType = $this->getEntityIdType($type);

            $types[$entityIdType] = [
                'type' => 'custom-scalar',
                'config' => [
                    'scalarType' => \sprintf('@=newObject("Sparklink\\\GraphQLToolsBundle\\\GraphQL\\\Doctrine\\\EntityIdType", [service("doctrine"), service("%s").getEntity("%s")])', $resolver, $type),
                ],
            ];
        }

        return [
            'fields' => [],
            'types' => $types,
        ];
    }
}
