<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\Entity\Trait;

use Overblog\GraphQLBundle\Annotation as GQL;

trait TypeStringableTrait
{
    #[GQL\Field(name: '_name')]
    public function _typeToString(): string
    {
        if (method_exists($this, '__toString')) {
            return $this->__toString();
        }

        return sprintf('%s:%s', static::class, $this->getId());
    }
}
