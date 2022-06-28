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

    public function __construct(iterable $managers)
    {
        $this->managers = $managers instanceof Traversable ? iterator_to_array($managers) : $managers;
    }

    public function getManager(string $type): EntityTypeManagerInterface
    {
        /* Use specific manager if exists */
        if (isset($this->managers[$type])) {
            return $this->managers[$type];
        }

        /* Use default manager non shared service */
        $manager = $this->defaultManager();
        $manager->setType($type);

        return $manager;
    }

    #[SubscribedService]
    private function defaultManager(): DefaultEntityTypeManager
    {
        return $this->container->get(__CLASS__.'::'.__FUNCTION__);
    }
}
