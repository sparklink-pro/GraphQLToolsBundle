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
        $configuration = $this->getConfiguration($builderConfig);

        $manager    = 'sparklink.types_manager';
        $properties = [];

        $configTypes = $configuration['types'];
        foreach ($configTypes as $type => $configuration) {
            if (!\array_key_exists('operations', $configuration)) {
                throw new Error('Missing "operations" key in configuration for type "'.$type.'".');
            }

            // Implement in the future
            $idType     = $configuration['idType'] ?? 'Int';

            $nameCreate = $this->getNameOperation($builderConfig, $type, self::OPERATION_CREATE);
            $nameUpdate = $this->getNameOperation($builderConfig, $type, self::OPERATION_UPDATE);
            $nameDelete = $this->getNameOperation($builderConfig, $type, self::OPERATION_DELETE);
            $inputType  = sprintf('%sInput', $type);

            $access = [];

            if ($this->isOperationActive($builderConfig, $type, self::OPERATION_CREATE)) {
                $access                 = $this->getAccess($builderConfig, $type, self::OPERATION_CREATE);
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
                $access                  = $this->getAccess($builderConfig, $type, self::OPERATION_UPDATE);
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
                $access                  = $this->getAccess($builderConfig, $type, self::OPERATION_DELETE);
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
