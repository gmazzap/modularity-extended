<?php

declare(strict_types=1);

namespace Gmazzap\ModularityExtended\Definition;

interface DependencyAwareDefinition extends Definition
{
    /**
     * @return list<string>
     */
    public function dependencies(): array;
}
