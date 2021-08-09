<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\Entity\Trait;

use Overblog\GraphQLBundle\Annotation as GQL;

trait TypeLinkedTrait
{
    #[GQL\Field(name: '_links', type: 'Json', resolve: '@=call(service("Sparklink\\\\GraphQLToolsBundle\\\\GraphQL\\\\Resolver\\\\LinkedTypesResolver").getLinkedTypes, [value])')]
    protected $_links;
}
