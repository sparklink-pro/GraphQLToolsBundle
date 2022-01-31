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
        $hasList     = false;
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
                    'resolve'     => sprintf('@=call(service("%s").getManager("%s").item, %s)', $manager, $type, '[args["id"], args.getArrayCopy(), info]'),
                ] + $access + $public;
            }

            if ($this->isOperationActive($configuration, $type, self::OPERATION_LIST)) {
                $hasList                  = true;
                $access                   = $this->getAccess($configuration, $type, self::OPERATION_LIST);
                $public                   = $this->getPublic($configuration, $type, self::OPERATION_LIST);
                $criterias                = $configType['list']['criterias'] ?? $configuration['default']['list']['criterias'] ?? [];
                $orders                   = $configType['list']['orderBy'] ?? $configuration['default']['list']['orderBy'] ?? [];

                $properties[$nameFindAll] = [
                    'description' => sprintf('Find all objects of type %s ', $type),
                    'type'        => $payloadType,
                    'resolve'     => sprintf('@=call(service("%s").getManager("%s").list, %s)', $manager, $type, sprintf('[%s, %s, args.getArrayCopy(), info]', json_encode($criterias), json_encode($orders))),
                    'args'        => [
                        'limit'   => ['type' => 'Int'],
                        'offset'  => ['type' => 'Int'],
                        'orderBy' => ['type' => '[OrderListInput!]'],
                    ],
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

        if ($hasList) {
            $types['OrderListOrder'] = [
                'type'   => 'enum',
                'config' => [
                    'values' => [
                        'ASC'  => ['value' => 'ASC'],
                        'DESC' => ['value' => 'DESC'],
                    ],
                ],
            ];

            $types['OrderListInput'] = [
                'type'   => 'input-object',
                'config' => [
                    'fields' => [
                        'field' => ['type' => 'String!'],
                        'order' => ['type' => 'OrderListOrder', 'defaultValue' => 'ASC'],
                    ],
                ],
            ];
        }

        return [
            'fields' => $properties,
            'types'  => $types,
        ];
    }
}
