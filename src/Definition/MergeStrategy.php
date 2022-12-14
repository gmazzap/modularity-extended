<?php

declare(strict_types=1);

namespace Gmazzap\ModularityExtended\Definition;

#[\Attribute(\Attribute::TARGET_CLASS)]
class MergeStrategy
{
    public const MERGE_NONE = 0;
    public const MERGE_SINGLETON = 2;
    public const MERGE_DEPENDENCIES = 4;
    public const MERGE_META = 8;
    public const MERGE_ALL = self::MERGE_SINGLETON|self::MERGE_DEPENDENCIES|self::MERGE_META;

    public function __construct(private int $flags)
    {
    }

    public function mergeSingleton(): bool
    {
        return ($this->flags & self::MERGE_SINGLETON) === self::MERGE_SINGLETON;
    }

    public function mergeDependencies(): bool
    {
        return ($this->flags & self::MERGE_DEPENDENCIES) === self::MERGE_DEPENDENCIES;
    }

    public function mergeMeta(): bool
    {
        return ($this->flags & self::MERGE_META) === self::MERGE_META;
    }
}
