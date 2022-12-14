<?php

declare(strict_types=1);

namespace Gmazzap\ModularityExtended\Event;

abstract class DisablingServiceEvent
{
    protected bool $allowed = true;

    /**
     * @return void
     */
    public function disableService(): void
    {
        $this->allowed = false;
    }

    /**
     * @return void
     */
    public function enableService(): void
    {
        $this->allowed = true;
    }

    /**
     * @return bool
     */
    public function isServiceEnabled(): bool
    {
        return $this->allowed;
    }
}
