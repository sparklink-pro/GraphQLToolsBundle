<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\Manager;

use Symfony\Contracts\Service\Attribute\SubscribedService;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;
use Traversable;

class EntityTypesManager implements ServiceSubscriberInterface
{
    use ServiceSubscriberTrait;

    protected array $managers = [];
    protected bool $useV2;

    public function __construct(iterable $managers, bool $useV2 = false)
    {
        $this->managers = $managers instanceof Traversable ? iterator_to_array($managers) : $managers;
        $this->useV2    = $useV2;
    }

    public function getManager(string $type): EntityTypeManagerInterface
    {
        /* Use specific manager if exists */
        if (isset($this->managers[$type])) {
            return $this->managers[$type];
        }

        /* Use default manager non shared service */
        $manager = $this->useV2 ? $this->defaultManagerV2() : $this->defaultManager();
        $manager->setType($type);

        return $manager;
    }

    #[SubscribedService]
    private function defaultManager(): DefaultEntityTypeManager
    {
        return $this->container->get(__CLASS__.'::'.__FUNCTION__);
    }

    #[SubscribedService]
    private function defaultManagerV2(): EntityTypeManager
    {
        return $this->container->get(__CLASS__.'::'.__FUNCTION__);
    }
}
