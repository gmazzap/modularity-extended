<?php

declare(strict_types=1);

namespace Gmazzap\ModularityExtended\Definition;

interface MetadataAwareDefinition extends Definition
{
    /**
     * @return array<string, mixed>
     */
    public function metadata(): array;
}
