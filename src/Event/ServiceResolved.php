<?php

declare(strict_types=1);

namespace Gmazzap\ModularityExtended\Event;

use Gmazzap\ModularityExtended\DefinitionInfo;
use Psr\Container\ContainerInterface;

class ServiceResolved implements Event
{
    use StoppableTrait;

    /**
     * @param DefinitionInfo $info
     * @param mixed $service
     * @param ContainerInterface $container
     */
    public function __construct(
        private DefinitionInfo $info,
        private mixed $service,
        private ContainerInterface $container
    ) {
    }

    /**
     * @return DefinitionInfo
     */
    public function info(): DefinitionInfo
    {
        return $this->info;
    }

    /**
     * @return mixed
     */
    public function service(): mixed
    {
        return $this->service;
    }

    /**
     * @return ContainerInterface
     */
    public function container(): ContainerInterface
    {
        return $this->container;
    }
}
