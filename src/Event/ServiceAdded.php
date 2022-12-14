<?php

declare(strict_types=1);

namespace Gmazzap\ModularityExtended\Event;

use Gmazzap\ModularityExtended\DefinitionInfo;
use Inpsyde\Modularity\Properties\Properties;

class ServiceAdded implements Event
{
    use StoppableTrait;

    /**
     * @param DefinitionInfo $info
     * @param string $moduleId
     * @param Properties $properties
     */
    public function __construct(
        private DefinitionInfo $info,
        private string $moduleId,
        private Properties $properties
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
     * @return string
     */
    public function moduleId(): string
    {
        return $this->moduleId;
    }

    /**
     * @return Properties
     */
    public function properties(): Properties
    {
        return $this->properties;
    }
}
