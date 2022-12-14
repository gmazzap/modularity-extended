<?php

declare(strict_types=1);

namespace Gmazzap\ModularityExtended\Container;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class DelegateContainer implements ContainerInterface
{
    /** @var non-empty-list<ContainerInterface> */
    private array $containers;
    /** @var int */
    private int $count;

    /**
     * @param ContainerInterface $container
     * @param list<ContainerInterface> $containers
     * @return DelegateContainer
     *
     * @no-named-arguments
     */
    public static function new(
        ContainerInterface $container,
        ContainerInterface ...$containers
    ): DelegateContainer {

        return new self($container, ...$containers);
    }

    /**
     * @param non-empty-list<ContainerInterface> $containers
     *
     * @no-named-arguments
     */
    private function __construct(ContainerInterface ...$containers)
    {
        /** @var non-empty-list<ContainerInterface> $containers */
        $this->containers = $containers;
        $this->count = count($containers);
    }

    /**
     * @param string $id
     * @return mixed
     */
    public function get(string $id): mixed
    {
        if ($this->count === 1) {
            return $this->containers[0]->get($id);
        }
        foreach ($this->containers as $container) {
            if ($container->has($id)) {
                return $container->get($id);
            }
        }
        throw new class("Service {$id} not found.")
            extends \Error
            implements NotFoundExceptionInterface {};
    }

    /**
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool
    {
        foreach ($this->containers as $container) {
            if ($container->has($id)) {
                return true;
            }
        }

        return false;
    }
}
