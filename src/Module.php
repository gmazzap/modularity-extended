<?php

declare(strict_types=1);

namespace Gmazzap\ModularityExtended;

use Gmazzap\ModularityExtended\Definition\Definition;

interface Module extends \Inpsyde\Modularity\Module\Module
{
    /**
     * @return array<Definition>
     */
    public function definitions(): array;
}
