<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\Entity\Interface;

use Overblog\GraphQLBundle\Annotation as GQL;

interface TypeStringableInterface
{
    #[GQL\Field(name: '_name')]
    public function _typeToString(): string;
}
