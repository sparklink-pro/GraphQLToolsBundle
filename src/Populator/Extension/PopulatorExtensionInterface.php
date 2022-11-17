<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\Manager;

interface PopulatorExtensionInterface
{
    public function supports(mixed $value): bool;

    public function populate(object $entity, string $property, mixed $value);
}
