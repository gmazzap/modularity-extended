<?php

declare(strict_types=1);

namespace Gmazzap\ModularityExtended\Definition;

interface SingletonAwareDefinition extends Definition
{
    public function isSingleton(): bool;
}
