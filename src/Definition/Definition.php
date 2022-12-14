<?php

declare(strict_types=1);

namespace Gmazzap\ModularityExtended\Definition;

use Gmazzap\ModularityExtended\DefinitionInfo;
use Psr\Container\ContainerInterface;

interface Definition
{
    public function id(): string;

    public function withPrevious(DefinitionInfo $previous): Definition;

    public function define(ContainerInterface $container): mixed;
}
