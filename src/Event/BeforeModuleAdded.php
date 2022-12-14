<?php

declare(strict_types=1);

namespace Gmazzap\ModularityExtended\Event;

use Inpsyde\Modularity\Properties\Properties;

class BeforeModuleAdded implements Event
{
    use StoppableTrait;

    private bool $moduleAllowed = true;

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

    /**
     * @return void
     */
    public function disableModule(): void
    {
        $this->moduleAllowed = false;
    }

    /**
     * @return void
     */
    public function enableModule(): void
    {
        $this->moduleAllowed = true;
    }

    /**
     * @return bool
     */
    public function isModuleAllowed(): bool
    {
        return $this->moduleAllowed;
    }
}
