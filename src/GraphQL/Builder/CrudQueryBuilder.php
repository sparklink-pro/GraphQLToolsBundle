<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Builder;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;

class CrudQueryBuilder extends CrudBuilder implements MappingInterface
{
    public const OPERATION_GET  = 'get';
    public const OPERATION_LIST = 'list';

    public function toMappingDefinition(array $builderConfig): array
    {
        $configuration = $this->getConfiguration($builderConfig);

        $manager    = 'sparklink.types_manager';
        $properties = [];
        $types      = [];

        $configTypes = $configuration['types'];

        foreach ($configTypes as $type => $configType) {
            $nameFind    = $this->getNameOperation($configuration, $type, self::OPERATION_GET);
            $nameFindAll = $this->getNameOperation($configuration, $type, self::OPERATION_LIST);
            $payloadType = sprintf('%sPayload', $nameFindAll);

            if ($this->isOperationActive($configuration, $type, self::OPERATION_GET)) {
                $access                 = $this->getAccess($configuration, $type, self::OPERATION_GET);
                $public                 = $this->getPublic($configuration, $type, self::OPERATION_GET);

                $properties[$nameFind]  = [
                    'args' => [
                        'id' => ['type' => sprintf('%s!', $this->getEntityIdType($type))],
                    ],
                    'description' => sprintf('Find a %s by id', $type),
                    'type'        => $type,
                    'resolve'     => sprintf('@=call(service("%s").getManager("%s").item, %s)', $manager, $type, '[args["id"]]'),
                    ] + $access + $public;
            }

            $filters = $configType['filters'] ?? [];

            if ($this->isOperationActive($configuration, $type, self::OPERATION_LIST)) {
                $access                   = $this->getAccess($configuration, $type, self::OPERATION_LIST);
                $public                   = $this->getPublic($configuration, $type, self::OPERATION_LIST);
                $orders                   = $configType['list']['orderBy'] ?? $configuration['default']['list']['orderBy'] ?? [];

                $orderBy                  = sprintf('{%s}', implode(', ', array_map(fn ($property, $order) => sprintf('"%s" : "%s"', $order, $property), array_values($orders), array_keys($orders))));
                $properties[$nameFindAll] = [
                    'description' => sprintf('Find all objects of type %s ', $type),
                    'type'        => $payloadType,
                    'resolve'     => sprintf('@=call(service("%s").getManager("%s").list, %s)', $manager, $type, sprintf('[args.getArrayCopy(), %s]', $orderBy)),
                    'args'        => $filters,
                ] + $access + $public;

                $types[$payloadType] = [
                    'type'   => 'object',
                    'config' => [
                        'fields' => [
                            'items' => sprintf('[%s!]!', $type),
                        ],
                    ],
                ];
            }
        }

        return [
            'fields' => $properties,
            'types'  => $types,
        ];
    }
}
