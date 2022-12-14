<?php

declare(strict_types=1);

namespace Gmazzap\ModularityExtended;

use Gmazzap\ModularityExtended\Definition\Definition;
use Gmazzap\ModularityExtended\Event\BeforeServiceDefinitionAdded;
use Gmazzap\ModularityExtended\Event\ServiceDefinitionAdded;
use Gmazzap\ModularityExtended\Event\ServiceDefinitionDetermined;
use Gmazzap\ModularityExtended\Event\ServiceDefinitionExtended;
use Gmazzap\ModularityExtended\Event\ServiceDefinitionNotAdded;
use Gmazzap\ModularityExtended\Event\ServiceDefinitionUnset;
use Gmazzap\ModularityExtended\Event\ServiceDefinitionReplaced;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @template-implements \IteratorAggregate<string, list<DefinitionInfo>>
 */
class Definitions implements \Countable, \IteratorAggregate, \JsonSerializable
{
    /** @var array<string, list<DefinitionInfo>> */
    private array $definitions = [];
    /** @var array<string, DefinitionInfo|null>  */
    private array $cached = [];
    private EventDispatcherInterface $dispatcher;

    /**
     * @return Definitions
     */
    public static function new(): Definitions
    {
        return new self();
    }

    /**
     * @param EventDispatcherInterface $dispatcher
     * @return static
     */
    public function withDispatcher(EventDispatcherInterface $dispatcher): static
    {
        $this->dispatcher = $dispatcher;

        return $this;
    }

    /**
     * @return EventDispatcherInterface
     */
    public function dispatcher(): EventDispatcherInterface
    {
        return $this->dispatcher;
    }

    /**
     */
    private function __construct()
    {
        $this->dispatcher = new NoopEventDispatcher();
    }

    /**
     * @param Definition $definition
     * @return DefinitionInfo|null
     */
    public function add(Definition $definition): DefinitionInfo|null
    {
        $info = DefinitionInfo::new($definition);
        $event = new BeforeServiceDefinitionAdded($info, $this);
        $this->dispatcher->dispatch($event);
        if (!$event->isServiceEnabled()) {
            $this->dispatcher->dispatch(new ServiceDefinitionNotAdded($info, $this));

            return null;
        }

        $id = $definition->id();

        if (isset($this->cached[$id])) {
            $this->dispatcher->dispatch(new ServiceDefinitionUnset($this->cached[$id], $this));
            unset($this->cached[$id]);
        }

        isset($this->definitions[$id]) or $this->definitions[$id] = [];
        $this->definitions[$id][] = $info;

        $this->dispatcher->dispatch(new ServiceDefinitionAdded($info, $this));

        return $info;
    }

    /**
     * @param string $id
     * @return DefinitionInfo|null
     */
    public function get(string $id): ?DefinitionInfo
    {
        if (array_key_exists($id, $this->cached)) {
            return $this->cached[$id];
        }

        $extensions = [];
        /** @var DefinitionInfo|null $service */
        $service = null;

        foreach ($this->definitions[$id] ?? [] as $info) {
            if ($info->isExtension()) {
                $extensions[] = $info;
                continue;
            }
            if ($service) {
                $info = DefinitionInfo::replace($info, $service);
                $this->dispatcher->dispatch(new ServiceDefinitionReplaced($info, $service, $this));
            }
            $service = $info;
        }

        if ($service) {
            foreach ($extensions as $info) {
                $info = DefinitionInfo::merge($info, $service);
                $this->dispatcher->dispatch(new ServiceDefinitionExtended($info, $service, $this));
                $service = $info;
            }
            $this->dispatcher->dispatch(new ServiceDefinitionDetermined($service, $this));
        }
        $this->cached[$id] = $service;

        return $service;
    }

    /**
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool
    {
        return !empty($this->definitions[$id]);
    }

    /**
     * @return \Iterator<string, list<DefinitionInfo>>
     */
    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->definitions);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->definitions);
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function jsonSerialize(): array
    {
        $data = [];
        foreach ($this->definitions as $id => $infos) {
            $data[$id] = [];
            foreach ($infos as $info) {
                $data[$id][] = $info->jsonSerialize();
            }
        }

        return $data;
    }
}
