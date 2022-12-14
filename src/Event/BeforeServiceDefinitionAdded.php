<?php

declare(strict_types=1);

namespace Gmazzap\ModularityExtended\Event;

use Gmazzap\ModularityExtended\DefinitionInfo;
use Gmazzap\ModularityExtended\Definitions;

class BeforeServiceDefinitionAdded extends DisablingServiceEvent
{
    use StoppableTrait;

    /**
     * @param DefinitionInfo $info
     * @param Definitions $definitions
     */
    public function __construct(private DefinitionInfo $info, private Definitions $definitions) {
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
