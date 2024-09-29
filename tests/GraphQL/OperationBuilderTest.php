<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\Test\GraphQL\Builder;

use PHPUnit\Framework\TestCase;
use Sparklink\GraphQLToolsBundle\GraphQL\Builder\OperationMutationBuilder;
use Sparklink\GraphQLToolsBundle\GraphQL\Builder\OperationQueryBuilder;

class OperationBuilderTest extends TestCase
{
    public function testOperations()
    {
        $configuration = [
            'configuration' => __DIR__.'/Fixtures/crud.yaml',
        ];



        $query = (new OperationQueryBuilder())->toMappingDefinition($configuration);
        $mutation = (new OperationMutationBuilder())->toMappingDefinition($configuration);

        
        $this->assertArrayNotHasKey('CommentUndelete', $mutation);
    }
}
