<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\Manager;

use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;
use Traversable;

class EntityTypesManager implements ServiceSubscriberInterface
{
    use ServiceSubscriberTrait;

    protected array $managers = [];

    public function __construct(iterable $managers)
    {
        $this->managers = $managers instanceof Traversable ? iterator_to_array($managers) : $managers;
    }

    public function getManager(string $type): EntityTypeManagerInterface
    {
        $manager = $this->managers[$type] ?? $this->defaultManager();
        $manager->setType($type);

        return $manager;
    }

    private function defaultManager(): DefaultEntityTypeManager
    {
        return $this->container->get(__METHOD__);
    }
}
