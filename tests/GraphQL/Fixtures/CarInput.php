<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\Tests\GraphQL\Fixtures;

class CarInput
{
    public ?int $id = null;
    public ?string $name;
    public ?string $model;
    public ?string $year;
    public ?string $color;
    public ?Person $owner;
}
