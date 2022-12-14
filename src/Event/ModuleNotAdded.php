<?php

declare(strict_types=1);

namespace Gmazzap\ModularityExtended\Event;

use Inpsyde\Modularity\Properties\Properties;

class ModuleNotAdded implements Event
{
    use StoppableTrait;

    /**
     * @param string $moduleId
     * @param Properties $properties
     */
    public function __construct(
        private string $moduleId,
        private Properties $properties
    ) {
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
