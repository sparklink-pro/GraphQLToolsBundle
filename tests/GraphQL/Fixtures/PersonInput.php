<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\Tests\GraphQL\Fixtures;

class PersonInput
{
    public ?string $fullName;
    public ?string $age;

    /** @var CarInput[] */
    public array $cars;
}
