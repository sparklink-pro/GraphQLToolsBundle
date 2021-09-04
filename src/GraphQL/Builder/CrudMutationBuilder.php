<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Builder;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;

class CrudMutationBuilder extends CrudBuilder implements MappingInterface
{
    public function toMappingDefinition(array $configTypes): array
    {
        $manager    = 'sparklink.types_manager';
        $properties = [];

        foreach ($configTypes as $type => $configuration) {
            $idType      = $configuration['idType'] ?? 'Int';
            $isReference = $configuration['reference'] ?? false;

            $hasMutations = !isset($configuration['mutation']) || false !== $configuration['mutation'];

            if ($hasMutations) {
                $isCreatable = $configuration['mutation']['creatable'] ?? true;
                $isDeletable = $configuration['mutation']['deletable'] ?? true;
                $permission  = $configuration['permission'] ?? false;

                $nameUpdate = sprintf('update%s', $type);
                $nameDelete = sprintf('delete%s', $type);
                $inputType  = $isReference ? 'ReferenceInput' : sprintf('%sInput', $type);

                $access = [];
                if ($permission) {
                    $access = ['access' => sprintf('@=perm("%s")', $permission)];
                }

                $properties[$nameUpdate] = [
                    'args' => [
                        'item'  => ['type' => sprintf('%s%s', $this->getEntityIdType($type), $isCreatable ? '' : '!')],
                        'input' => ['type' => sprintf('%s!', $inputType)],
                    ],
                    'description' => sprintf('Update or create an object of type %s', $type),
                    'type'        => $configuration['mutationType'] ?? $type,
                    'resolve'     => sprintf('@=call(service("%s").getManager("%s").update, arguments({item: "%s", input: "%s"}, args))', $manager, $type, $idType, $inputType),
                ] + $access;

                if ($isDeletable) {
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
        }

        return $properties;
    }
}
