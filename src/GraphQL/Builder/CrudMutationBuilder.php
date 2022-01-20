<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Builder;

use Error;
use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;

class CrudMutationBuilder extends CrudBuilder implements MappingInterface
{
    public const OPERATION_CREATE = 'create';
    public const OPERATION_UPDATE = 'update';
    public const OPERATION_DELETE = 'delete';

    public function toMappingDefinition(array $builderConfig): array
    {
        $manager    = 'sparklink.types_manager';
        $properties = [];

        $configTypes = $builderConfig['types'];
        foreach ($configTypes as $type => $configuration) {
            if ('Position' !== $type) {
                continue;
            }

            if (!\array_key_exists('operations', $configuration)) {
                throw new Error('Missing "operations" key in configuration for type "'.$type.'".');
            }

            $idType = $configuration['idType'] ?? 'Int';

            $permission  = $configuration['permission'] ?? false;

            $nameCreate = sprintf('%sCreate', $type);
            $nameUpdate = sprintf('%sUpdate', $type);
            $nameDelete = sprintf('%sDelete', $type);
            $inputType  = sprintf('%sInput', $type);

            $access = [];
            // $access = $this->getAccess($builderConfig, $type);

            if ($this->isOperationActive($builderConfig, $type, self::OPERATION_CREATE)) {
                $properties[$nameCreate]= [
                    'args' => [
                        'input' => ['type' => $inputType],
                    ],
                    'description' => sprintf('Create a %s', $type),
                    'type'        => $configuration['mutationType'] ?? $type,
                    'resolve'     => sprintf('@=call(service("%s").getManager("%s").create, arguments({ input: "%s"}, args))', $manager, $type, $inputType),
                    ] + $access;
            }

            if ($this->isOperationActive($builderConfig, $type, self::OPERATION_UPDATE)) {
                $properties[$nameUpdate] = [
                    'args' => [
                        'item'  => ['type' => sprintf('%s!', $this->getEntityIdType($type))],
                        'input' => ['type' => sprintf('%s!', $inputType)],
                    ],
                    'description' => sprintf('Update or create an object of type %s', $type),
                    'type'        => $configuration['mutationType'] ?? $type,
                    'resolve'     => sprintf('@=call(service("%s").getManager("%s").update, arguments({item: "%s", input: "%s"}, args))', $manager, $type, $idType, $inputType),
                    ] + $access;
            }

            if ($this->isOperationActive($builderConfig, $type, self::OPERATION_DELETE)) {
                $properties[$nameDelete] = [
                        'args' => [
                            'item' => ['type' => sprintf('%s!', $this->getEntityIdType($type))],
                        ],
                        'description' => sprintf('Remove a object of type %s', $type),
                        'type'        => $configuration['mutationType'] ?? 'Boolean',
                        'resolve'     => sprintf('@=call(service("%s").getManager("%s").delete, arguments({item: "%s"}, args))', $manager, $type, $idType),
                    ] + $access;
            }
        }

        return $properties;
    }
}
