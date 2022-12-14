<?php

declare(strict_types=1);

namespace Gmazzap\ModularityExtended\Event;

use Gmazzap\ModularityExtended\Definition\Definition;
use Inpsyde\Modularity\Properties\Properties;

class ServiceNotAdded implements Event
{
    use StoppableTrait;

    /**
     * @param Definition $definition
     * @param string $moduleId
     * @param Properties $properties
     */
    public function __construct(
        private Definition $definition,
        private string $moduleId,
        private Properties $properties
    ) {
    }

    /**
     * @return Definition
     */
    public function definition(): Definition
    {
        return $this->definition;
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
