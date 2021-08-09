<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Builder;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;

class CrudQueryBuilder extends CrudBuilder implements MappingInterface
{
    public function toMappingDefinition(array $config): array
    {
        $resolver = 'graphql_resolver';
        $manager = 'sparklink.types_manager';
        $properties = [];
        $types = [];

        foreach ($this->types as $type => $configuration) {
            $idType = $configuration['idType'] ?? 'Int';
            $nameFind = $type;
            $nameFindAll = \sprintf('list%s', $type);
            $payloadType = \sprintf('%sPayload', $nameFindAll);
            $entityIdType = $this->getEntityIdType($type);

            $properties[$nameFind] = [
                'args' => [
                    'id' => ['type' => \sprintf('%s!', $this->getEntityIdType($type))],
                ],
                'description' => \sprintf('Find a %s by id', $type),
                'type' => $type,
                'resolve' => \sprintf('@=call(service("%s").getManager("%s").item, %s)', $manager, $type, '[args["id"]]'),
            ];

            $filters = $configuration['filters'] ?? [];
            $orders = $configuration['orderBy'] ?? 'id';

            if (!\is_array($orders)) {
                $orders = [$orders];
            }

            $orderBy = \sprintf('{%s}', \join(', ', \array_map(fn ($order) => \sprintf('"%s" : "%s"', $order, 'ASC'), $orders)));
            $properties[$nameFindAll] = [
                'description' => \sprintf('Find all objects of type %s ', $type),
                'type' => $payloadType,
                'resolve' => \sprintf('@=call(service("%s").getManager("%s").list, %s)', $manager, $type, \sprintf('[args.getArrayCopy(), %s]', $orderBy)),
                'args' => $filters,
            ];

            $types[$payloadType] = [
                'type' => 'object',
                'config' => [
                    'fields' => [
                        'items' => \sprintf('[%s!]!', $type),
                    ],
                ],
            ];
            $types[$entityIdType] = [
                'type' => 'custom-scalar',
                'config' => [
                    'scalarType' => \sprintf('@=newObject("Sparklink\\\GraphQLToolsBundle\\\GraphQL\\\Doctrine\\\EntityIdType", [service("doctrine"), service("%s").getEntity("%s")])', $resolver, $type),
                ],
            ];
        }

        return [
            'fields' => $properties,
            'types' => $types,
        ];
    }
}
