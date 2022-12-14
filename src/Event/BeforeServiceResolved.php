<?php

declare(strict_types=1);

namespace Gmazzap\ModularityExtended\Event;

use Gmazzap\ModularityExtended\DefinitionInfo;
use Psr\Container\ContainerInterface;

class BeforeServiceResolved extends DisablingServiceEvent
{
    use StoppableTrait;

    public function __construct(
        private DefinitionInfo $info,
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
     * @return ContainerInterface
     */
    public function container(): ContainerInterface
    {
        return $this->container;
    }
}
