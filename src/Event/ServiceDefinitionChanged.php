<?php

declare(strict_types=1);

namespace Gmazzap\ModularityExtended\Event;

use Gmazzap\ModularityExtended\DefinitionInfo;
use Gmazzap\ModularityExtended\Definitions;

abstract class ServiceDefinitionChanged
{
    use StoppableTrait;

    /**
     * @param DefinitionInfo $info
     * @param DefinitionInfo $previous
     * @param Definitions $definitions
     */
    public function __construct(
        protected DefinitionInfo $info,
        protected DefinitionInfo $previous,
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
     * @return DefinitionInfo
     */
    public function previous(): DefinitionInfo
    {
        return $this->previous;
    }

    /**
     * @return Definitions
     */
    public function definitions(): Definitions
    {
        return $this->definitions;
    }
}
