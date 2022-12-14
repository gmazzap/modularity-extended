<?php

declare(strict_types=1);

namespace Gmazzap\ModularityExtended\Definition;

#[\Attribute(\Attribute::TARGET_CLASS|\Attribute::IS_REPEATABLE)]
class Meta
{
    public function __construct(private string $key, private mixed $value)
    {
    }

    public function key(): string
    {
        return $this->key;
    }

    public function value(): mixed
    {
        return $this->value;
    }
}
