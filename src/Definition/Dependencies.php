<?php

declare(strict_types=1);

namespace Gmazzap\ModularityExtended\Definition;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Dependencies
{
    /** @var list<string> */
    private array $dependencies;

    /**
     * @param list<string> $dependencies
     *
     * @no-named-arguments
     */
    public function __construct(string ...$dependencies)
    {
        $this->dependencies = $dependencies;
    }

    /**
     * @return list<string>
     */
    public function dependencies(): array
    {
        return $this->dependencies;
    }
}
