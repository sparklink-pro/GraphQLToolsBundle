<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Builder;

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
        foreach ($configTypes as $type => $configType) {
            // Implement in the future
            $idType     = $configType['idType'] ?? 'Int';

            $nameCreate = $this->getNameOperation($configuration, $type, self::OPERATION_CREATE);
            $nameUpdate = $this->getNameOperation($configuration, $type, self::OPERATION_UPDATE);
            $nameDelete = $this->getNameOperation($configuration, $type, self::OPERATION_DELETE);
            $inputType  = sprintf('%sInput', $type);

            $access = [];

            if ($this->isOperationActive($configuration, $type, self::OPERATION_CREATE)) {
                $access                 = $this->getAccess($configuration, $type, self::OPERATION_CREATE);
                $public                 = $this->getPublic($configuration, $type, self::OPERATION_CREATE);
                $properties[$nameCreate]= [
                    'args' => [
                        'input' => ['type' => $inputType],
                    ],
                    'description' => sprintf('Create a %s', $type),
                    'type'        => $configType['mutationType'] ?? $type,
                    'resolve'     => sprintf('@=call(service("%s").getManager("%s").create, arguments({ input: "%s"}, args))', $manager, $type, $inputType),
                ] + $access + $public;
            }

            if ($this->isOperationActive($configuration, $type, self::OPERATION_UPDATE)) {
                $access                  = $this->getAccess($configuration, $type, self::OPERATION_UPDATE);
                $public                  = $this->getPublic($configuration, $type, self::OPERATION_UPDATE);
                $properties[$nameUpdate] = [
                    'args' => [
                        'item'  => ['type' => sprintf('%s!', $this->getEntityIdType($type))],
                        'input' => ['type' => sprintf('%s!', $inputType)],
                    ],
                    'description' => sprintf('Update or create an object of type %s', $type),
                    'type'        => $configType['mutationType'] ?? $type,
                    'resolve'     => sprintf('@=call(service("%s").getManager("%s").update, arguments({input: "%s", item: "%s"}, args))', $manager, $type, $inputType, $idType),
                ] + $access + $public;
            }

            if ($this->isOperationActive($configuration, $type, self::OPERATION_DELETE)) {
                $access                  = $this->getAccess($configuration, $type, self::OPERATION_DELETE);
                $public                  = $this->getPublic($configuration, $type, self::OPERATION_DELETE);
                $properties[$nameDelete] = [
                    'args' => [
                        'item' => ['type' => sprintf('%s!', $this->getEntityIdType($type))],
                    ],
                    'description' => sprintf('Remove a object of type %s', $type),
                    'type'        => $configType['mutationType'] ?? 'Boolean',
                    'resolve'     => sprintf('@=call(service("%s").getManager("%s").delete, arguments({item: "%s"}, args))', $manager, $type, $idType),
                ] + $access + $public;
            }
        }

        return $properties;
    }
}
