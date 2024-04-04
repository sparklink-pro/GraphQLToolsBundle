<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Builder\Operation;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

use function Symfony\Component\String\s;

abstract class Operation implements OperationInterface
{
    protected string $type;

    protected array $options;

    public function __construct(string $type, array $options = [])
    {
        try {
            $processor = new Processor();
            $this->type = $type;
            $this->options = $processor->processConfiguration(
                $this,
                [$options]
            );
        } catch (\Exception $e) {
            throw new \Exception(sprintf('Error processing configuration for type "%s", operation: "%s":  %s', $this->type, static::class, $e->getMessage()));
        }
    }

    /**
     * Return the operation type: Query or Mutation.
     */
    abstract public function getOperationType(): OperationType;

    /**
     * Return the type of the operation.
     */
    abstract protected function getType(): string;

    /**
     * Return the input type name related to the type
     * Ex: User -> UserInput.
     */
    protected function getInputType(): string
    {
        return sprintf('%sInput', $this->type);
    }

    /**
     * Return the scalar id type related to the type
     * Ex: User -> UserId.
     */
    protected function getScalarIdType(): string
    {
        return sprintf('%sId', $this->type);
    }

    /**
     * Get the argument name representing a type instance
     * Ex: User -> user.
     */
    protected function getArgType(): string
    {
        return s($this->type)->camel()->toString();
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        return new TreeBuilder('operation');
    }

    protected function getInferName(): string
    {
        $parts = explode('\\', static::class);
        $className = array_pop($parts);

        return preg_replace('/Operation$/', '', $className);
    }

    /**
     * Get the name of the operation.
     */
    protected function getName(): string
    {
        return sprintf('%s%s', $this->type, $this->getInferName());
    }

    /**
     * Return the corresponding method on the provider.
     */
    protected function getProviderMethod(): string
    {
        return lcfirst($this->getInferName());
    }

    /**
     * Get the description of the operation.
     */
    protected function getDescription(): string
    {
        return '';
    }

    /**
     * Get the args used by the operation.
     */
    protected function getArgs(): array
    {
        return [];
    }

    /**
     * Additional types to be added to the schema.
     */
    protected function getAdditionalTypes(): array
    {
        return [];
    }

    protected function getResolver(): string
    {
        $manager = 'sparklink.types_manager';

        return sprintf('@=call(service("%s").getManager("%s").%s, %s)', $manager, $this->type, $this->getProviderMethod(), $this->getResolverArguments());
    }

    protected function getResolverArguments(array $arguments = [], bool $wrapped = false): string
    {
        $map = [];
        $mainArgs = [];

        foreach ($arguments as $name => $type) {
            $type = \is_string($type) ? $type : $type['type'];
            $mainArgs[] = sprintf('args["%s"]', $name);
            $map[$name] = $type;
        }

        $defaultArgs = [];
        if ($wrapped) {
            foreach ($map as $_) {
                $defaultArgs[] = '""';
            }
        }
        $defaultArgs[] = 'args.getArrayCopy()';
        $defaultArgs[] = json_encode($this->options ?? []);
        $defaultArgs[] = 'info';

        if (!$wrapped) {
            return sprintf('[%s]', implode(', ', [...$mainArgs, ...$defaultArgs]));
        }

        return sprintf('arguments(%s, args) + [%s]', json_encode($map), implode(', ', $defaultArgs));
    }

    public function getMapping(): array
    {
        $fields = [];
        if (OperationType::NONE !== $this->getOperationType()) {
            $fields = [
                $this->getName() => [
                    'type' => $this->getType(),
                    'description' => $this->getDescription(),
                    'args' => $this->getArgs(),
                    'resolve' => $this->getResolver(),
                ],
            ];
        }

        return [
            'fields' => $fields,
            'types' => $this->getAdditionalTypes(),
        ];
    }
}
