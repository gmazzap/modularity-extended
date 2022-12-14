<?php

declare(strict_types=1);

namespace Gmazzap\ModularityExtended\Definition;

interface ExtensionAwareDefinition extends Definition
{
    public function isExtension(): bool;
}
