<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Builder;

use Error;
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

        foreach ($configTypes as $type => $configuration) {
            if (!\array_key_exists('operations', $configuration)) {
                throw new Error('Missing "operations" key in configuration for type "'.$type.'".');
            }

            $nameFind    = $this->getNameOperation($builderConfig, $type, self::OPERATION_GET);
            $nameFindAll = $this->getNameOperation($builderConfig, $type, self::OPERATION_LIST);
            $payloadType = sprintf('%sPayload', $nameFindAll);
            $access      = [];

            if ($this->isOperationActive($builderConfig, $type, self::OPERATION_GET)) {
                $access                 = $this->getAccess($builderConfig, $type, self::OPERATION_GET);

                $properties[$nameFind]  = [
                    'args' => [
                        'id' => ['type' => sprintf('%s!', $this->getEntityIdType($type))],
                    ],
                    'description' => sprintf('Find a %s by id', $type),
                    'type'        => $type,
                    'resolve'     => sprintf('@=call(service("%s").getManager("%s").item, %s)', $manager, $type, '[args["id"]]'),
                ] + $access;
            }

            $filters = $configuration['filters'] ?? [];
            $orders  = $configuration['list']['orderBy'] ?: $builderConfig['default']['list']['orderBy'] ?? ['id' => 'ASC'];

            if ($this->isOperationActive($builderConfig, $type, self::OPERATION_LIST)) {
                $access                   = $this->getAccess($builderConfig, $type, self::OPERATION_LIST);
                $orderBy                  = sprintf('{%s}', implode(', ', array_map(fn ($property, $order) => sprintf('"%s" : "%s"', $order, $property), array_values($orders), array_keys($orders))));
                $properties[$nameFindAll] = [
                    'description' => sprintf('Find all objects of type %s ', $type),
                    'type'        => $payloadType,
                    'resolve'     => sprintf('@=call(service("%s").getManager("%s").list, %s)', $manager, $type, sprintf('[args.getArrayCopy(), %s]', $orderBy)),
                    'args'        => $filters,
                ] + $access;
            }

            $types[$payloadType] = [
                'type'   => 'object',
                'config' => [
                    'fields' => [
                        'items' => sprintf('[%s!]!', $type),
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
