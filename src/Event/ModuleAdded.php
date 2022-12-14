<?php

declare(strict_types=1);

namespace Gmazzap\ModularityExtended\Event;

use Inpsyde\Modularity\Properties\Properties;

class ModuleAdded implements Event
{
    use StoppableTrait;

    /**
     * @param string $moduleId
     * @param array $serviceIds
     * @param Properties $properties
     */
    public function __construct(
        private string $moduleId,
        private array $serviceIds,
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
     * @return array
     */
    public function serviceIds(): array
    {
        return $this->serviceIds;
    }

    /**
     * @return Properties
     */
    public function properties(): Properties
    {
        return $this->properties;
    }
}
