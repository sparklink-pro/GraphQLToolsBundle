<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\Entity\Interface;

interface RankableEntityInterface
{
    function setRank(int $rank): self;
}
