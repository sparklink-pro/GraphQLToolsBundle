<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\Tests\GraphQL\Fixtures;

class Person
{
    public ?int $id;
    public ?string $fullName;
    public ?string $firstName;
    public ?string $age;
    /** @var Car[] */
    public array $cars = [];
}
