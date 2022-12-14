<?php

declare(strict_types=1);

namespace Gmazzap\ModularityExtended\Event;

use Gmazzap\ModularityExtended\DefinitionInfo;
use Psr\Container\ContainerInterface;

class ServiceNotResolved implements Event
{
    use StoppableTrait;

    private mixed $service = null;
    private bool $hasService = false;

    /**
     * @param string $serviceId
     * @param DefinitionInfo|null $info
     * @param \Throwable $error
     * @param ContainerInterface $container
     */
    public function __construct(
        private string $serviceId,
        private DefinitionInfo|null $info,
        private \Throwable $error,
        private ContainerInterface $container
    ) {
    }

    /**
     * @return string
     */
    public function serviceId(): string
    {
        return $this->serviceId;
    }

    /**
     * @return DefinitionInfo|null
     */
    public function info(): DefinitionInfo|null
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


    /**
     * @param mixed $service
     * @return void
     */
    public function recoverWithService(mixed $service): void
    {
        if ($service !== null) {
            $this->service = $service;
            $this->hasService = true;
        }
    }

    /**
     * @return bool
     */
    public function hasService(): bool
    {
        return $this->hasService;
    }

    /**
     * @return mixed
     */
    public function service(): mixed
    {
        if (!$this->hasService) {
            throw $this->error;
        }

        return $this->service;
    }
}
