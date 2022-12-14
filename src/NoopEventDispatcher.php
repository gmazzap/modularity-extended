<?php

declare(strict_types=1);

namespace Gmazzap\ModularityExtended;

use Psr\EventDispatcher\EventDispatcherInterface;

final class NoopEventDispatcher implements EventDispatcherInterface
{
    /**
     * @param object $event
     * @return object
     */
    public function dispatch(object $event): object
    {
        /*
         * How many roads must a man walk down
         * Before you call him a man?
         * How many seas must a white dove sail
         * Before she sleeps in the sand?
         * Yes, and how many times must the cannonballs fly
         * Before they're forever banned?
         *
         * The answer, my friend, is blowin' in the wind.
         */
        return $event;
    }
}
