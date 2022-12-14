<?php

declare(strict_types=1);

namespace Gmazzap\ModularityExtended\Event;

use Gmazzap\ModularityExtended\NoopEventDispatcher;

trait StoppableTrait
{
    /**
     * @var bool
     */
    private bool $stopped = false;

    /**
     * @return void
     */
    final public function stop(): void
    {
        $this->stopped = true;
    }

    /**
     * @return bool
     */
    final public function isPropagationStopped(): bool
    {
        return $this->stopped;
    }
}
