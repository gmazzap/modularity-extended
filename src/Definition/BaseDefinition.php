<?php

declare(strict_types=1);

namespace Gmazzap\ModularityExtended\Definition;

use Gmazzap\ModularityExtended\DefinitionInfo;

abstract class BaseDefinition implements Definition
{
    public function __construct(protected string $id)
    {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function withPrevious(DefinitionInfo $previous): Definition
    {
        return $this;
    }
}
