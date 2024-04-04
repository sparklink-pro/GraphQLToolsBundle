<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Builder\Operation;

enum OperationType: string
{
    case QUERY = 'query';
    case MUTATION = 'mutation';
}