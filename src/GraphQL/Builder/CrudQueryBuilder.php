<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Builder;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;

class CrudQueryBuilder extends CrudBuilder implements MappingInterface
{
    public function toMappingDefinition(array $configTypes): array
    {
        $manager = 'sparklink.types_manager';
        $properties = [];
        $types = [];

        foreach ($configTypes as $type => $configuration) {
            if (array_key_exists('query', $configuration) && $configuration['query'] === false) {
                continue;
            }
            $nameFind = $type;
            $nameFindAll = \sprintf('%sList', $type);
            $payloadType = \sprintf('%sPayload', $nameFindAll);

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
        }

        return [
            'fields' => $properties,
            'types' => $types,
        ];
    }
}
