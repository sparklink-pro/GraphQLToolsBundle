<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Doctrine;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Exception;
use GraphQL\Error\Error;
use GraphQL\Language\AST\BooleanValueNode;
use GraphQL\Language\AST\FloatValueNode;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\ObjectValueNode;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Language\AST\ValueNode;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;

final class EntityIdType extends ScalarType
{
    private ObjectManager $manager;
    private string $className;
    private ClassMetadata $metadata;

    public function __construct(ManagerRegistry $registry, string $className = null)
    {
        parent::__construct([]);
        $this->registry = $registry;
        $this->className = $className;
        $this->manager = $registry->getManagerForClass($className);
        $this->metadata = $this->manager->getClassMetadata($this->className);
    }

    public function serialize($entity)
    {
        $id = $this->metadata->getIdentifierValues($entity);
        if (1 === \count($id)) {
            return $id[\array_key_first($id)];
        }

        return $id;
    }

    public function parseLiteral($valueNode, array $variables = null)
    {
        $identifiers = $this->getIdentifiersTypesMap($this->className);

        if (\count($identifiers) >= 2) {
            if (!($valueNode instanceof ObjectValueNode)) {
                throw new Error('Invalid format specified. An object with identifiers is expected', Utils::printSafe($valueNode));
            }
            $nodes = [];
            foreach ($valueNode->fields as $node) {
                $name = $node->name->value;
                $nodes[$name] = $node->value;
            }
        } else {
            $nodes = [\array_key_first($identifiers) => $valueNode];
        }

        $id = [];
        foreach ($identifiers as $name => $doctrineType) {
            if (!$nodes[$name]) {
                throw new Error(\sprintf('The identifier field "%s" is missing from the identifiers', $name));
            } elseif (!$this->isAcceptableNodeForType($doctrineType, $nodes[$name])) {
                $acceptables = $this->getNodeClassesForType($doctrineType);
                throw new Error(\sprintf('The identifier field "%s" is expecting a value of type %s', \implode(' or ', \array_map($this->getValueNodeName, $acceptables))));
            } else {
                $id[$name] = $nodes[$name]->value;
            }
        }

        $entity = $this->manager->getRepository($this->className)->find($id);
        if (!$entity) {
            throw new Error(\sprintf('Requested entity not found'));
        }

        return $entity;
    }

    public function parseValue($id)
    {
        if (!$id) {
            return null;
        }

        return $this->manager->find($this->className, $id);
    }

    protected function getIdentifiersTypesMap(string $className): array
    {
        $identifiers = [];
        $metadata = $this->manager->getClassMetadata($className);
        foreach ($metadata->getIdentifierFieldNames() as $identifierField) {
            if ($metadata->hasAssociation($identifierField)) {
                $association = $metadata->getAssociationMapping($identifierField);
                $targetEntity = $association['targetEntity'];
                $metadataTarget = $this->manager->getClassMetadata($targetEntity);
                $column = $association['joinColumns'][0]['referencedColumnName'];
                $identifiers[$identifierField] = $metadataTarget->getTypeOfField($metadataTarget->getFieldName($column));
            } else {
                $identifiers[$identifierField] = $metadata->getTypeOfField($identifierField);
            }
        }

        return $identifiers;
    }

    /**
     * @throws Exception
     */
    protected function isAcceptableNodeForType(string $doctrineType, ValueNode $node): bool
    {
        foreach ($this->getNodeClassesForType($doctrineType) as $acceptedNode) {
            if ($node instanceof $acceptedNode) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string[]
     *
     * @throws Exception
     */
    protected function getNodeClassesForType(string $doctrineType): array
    {
        switch ($doctrineType) {
            case 'integer':
            case 'smallint':
            case 'bigint':
                return [IntValueNode::class];
            case 'double':
            case 'decimal':
            case 'float':
                return [IntValueNode::class, FloatValueNode::class];
            case 'string':
            case 'text':
                return [StringValueNode::class];
            case 'boolean':
                return [BooleanValueNode::class];
            default:
                throw new \Exception(\sprintf('Unable to validate Doctrine column type "%s" in Entity Scalar.', $doctrineType));
        }
    }

    protected function getValueNodeName(string $className): string
    {
        switch ($className) {
            case IntValueNode::class: return 'integer';
            case FloatValueNode::class: return 'float';
            case StringValueNode::class: return 'string';
            case BooleanValueNode::class: return 'boolean';
            default:
                return 'unknow';
        }
    }
}
