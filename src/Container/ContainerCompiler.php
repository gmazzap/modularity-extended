<?php

declare(strict_types=1);

namespace Gmazzap\ModularityExtended\Container;

use Gmazzap\ModularityExtended\Definition\CallableDefinition;
use Gmazzap\ModularityExtended\Definitions;
use Psr\Container\ContainerInterface;

class ContainerCompiler implements \Inpsyde\Modularity\Container\ContainerCompiler
{
    /** @var list<ContainerInterface> */
    protected array $containers = [];
    /** @var list<string> */
    protected array $extensionIds = [];

    /**
     * @param Definitions $definitions
     * @return ContainerCompiler
     */
    public static function new(Definitions $definitions): ContainerCompiler
    {
        return new self($definitions);
    }

    /**
     * @param Definitions $definitions
     */
    protected function __construct(protected Definitions $definitions)
    {
    }

    /**
     * @return Definitions
     */
    public function definitions(): Definitions
    {
        return $this->definitions;
    }

    /**
     * @param ContainerInterface $container
     * @return void
     */
    public function addContainer(ContainerInterface $container): void
    {
        $this->containers[] = $container;
    }

    /**
     * @param string $id
     * @param callable $factory
     * @return void
     */
    public function addFactory(string $id, callable $factory): void
    {
        $this->definitions->add(CallableDefinition::newFactory($id, $factory));
    }

    /**
     * @param string $id
     * @param callable $service
     * @return void
     */
    public function addService(string $id, callable $service): void
    {
        $this->definitions->add(CallableDefinition::newSingleton($id, $service));
    }

    /**
     * @param string $id
     * @return bool
     */
    public function hasService(string $id): bool
    {
        return $this->definitions->has($id);
    }

    /**
     * @param string $id
     * @param callable $extender
     * @return void
     */
    public function addExtension(string $id, callable $extender): void
    {
        $this->extensionIds[] = $id;
        $this->definitions->add(CallableDefinition::newExtension($id, $extender));
    }

    /**
     * @param string $id
     * @return bool
     */
    public function hasExtension(string $id): bool
    {
        return in_array($id, $this->extensionIds, true);
    }

    /**
     * @return ContainerInterface
     */
    public function compile(): ContainerInterface
    {
        return DelegateContainer::new(Container::new($this->definitions), ...$this->containers);
    }
}
