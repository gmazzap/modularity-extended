<?php

declare(strict_types=1);

namespace Gmazzap\ModularityExtended\Definition;

use Gmazzap\ModularityExtended\DefinitionInfo;
use Psr\Container\ContainerInterface;

class CallableDefinition implements
    DependencyAwareDefinition,
    MetadataAwareDefinition,
    SingletonAwareDefinition,
    ExtensionAwareDefinition
{
    /** @var callable */
    private $factory;
    /** @var array<string, mixed> */
    private array $meta = [];
    /** @var list<string> */
    private array $dependencies = [];
    private DefinitionInfo|null $previous = null;

    /**
     * @param string $id
     * @param callable $factory
     * @return CallableDefinition
     */
    public static function newFactory(string $id, callable $factory): CallableDefinition
    {
        return new self($id, $factory, false, false);
    }

    /**
     * @param string $id
     * @param callable $factory
     * @return CallableDefinition
     */
    public static function newSingleton(string $id, callable $factory): CallableDefinition
    {
        return new self($id, $factory, true, false);
    }

    /**
     * @param string $id
     * @param callable $factory
     * @return CallableDefinition
     */
    public static function newExtension(string $id, callable $factory): CallableDefinition
    {
        return new self($id, $factory, true, true);
    }

    /**
     * @param string $id
     * @param callable $factory
     * @param bool $isSingleton
     * @param bool $isExtension
     */
    private function __construct(
        private string $id,
        callable $factory,
        private bool $isSingleton,
        private bool $isExtension
    ) {
        $this->factory = $factory;
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function isSingleton(): bool
    {
        return $this->isSingleton;
    }

    /**
     * @return bool
     */
    public function isExtension(): bool
    {
        return $this->isExtension;
    }

    /**
     * @return callable
     */
    public function factory(): callable
    {
        return $this->factory;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function addMeta(string $key, mixed $value): static
    {
        $this->meta[$key] = $value;

        return $this;
    }

    /**
     * @param string $key
     * @return static
     */
    public function removeMeta(string $key): static
    {
        unset($this->meta[$key]);

        return $this;
    }

    /**
     * @param string $dependency
     * @param string ...$dependencies
     * @return $this
     */
    public function withDependencies(string $dependency, string ...$dependencies): static
    {
        array_unshift($dependencies, $dependency);
        /** @var list<string> $dependencies */
        $this->dependencies = $dependencies;

        return $this;
    }

    /**
     * @param string ...$dependencies
     * @return static
     */
    public function withoutDependencies(string ...$dependencies): static
    {
        /** @psalm-suppress PropertyTypeCoercion */
        $this->dependencies = $dependencies
            ? array_diff($this->dependencies, $dependencies)
            : [];

        return $this;
    }

    /**
     * @return list<string>
     */
    public function dependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return $this->meta;
    }

    /**
     * @param DefinitionInfo $previous
     * @return Definition
     */
    public function withPrevious(DefinitionInfo $previous): Definition
    {
        if ($this->isExtension) {
            $this->previous = $previous;
        }

        return $this;
    }

    /**
     * @param ContainerInterface $container
     * @return mixed
     */
    public function define(ContainerInterface $container): mixed
    {
        if ($this->previous) {
            $prevService = $this->previous->definition()->define($container);

            return ($this->factory)($prevService, $container);
        }

        return ($this->factory)($container);
    }
}
