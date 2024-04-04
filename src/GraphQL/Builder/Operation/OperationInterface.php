<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Builder\Operation;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

interface OperationInterface extends ConfigurationInterface
{
    public function __construct(string $type, array $options = []);

    /**
     * return an array of with configuration fields and optionnal new types
     *
     * @return array
     */
    public function getMapping(): array;

    public function getOperationType(): OperationType;
}