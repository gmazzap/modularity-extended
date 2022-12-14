<?php

declare(strict_types=1);

namespace Gmazzap\ModularityExtended\Event;

use Gmazzap\ModularityExtended\DefinitionInfo;
use Gmazzap\ModularityExtended\Definitions;

abstract class ServiceDefinitionProcessed
{
    use StoppableTrait;

    /**
     * @param DefinitionInfo $info
     * @param Definitions $definitions
     */
    public function __construct(
        protected DefinitionInfo $info,
        protected Definitions $definitions
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
     * @return Definitions
     */
    public function definitions(): Definitions
    {
        return $this->definitions;
    }
}
