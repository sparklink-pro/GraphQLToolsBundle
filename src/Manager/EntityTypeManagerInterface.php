<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\Manager;

interface EntityTypeManagerInterface
{
    public function getType(): string;

    public function getEntityClass(): string;

    public function setType(string $type): void;
}
