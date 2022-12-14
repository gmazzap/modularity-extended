<?php

declare(strict_types=1);

namespace Gmazzap\ModularityExtended\Container;

use Gmazzap\ModularityExtended\DefinitionInfo;
use Gmazzap\ModularityExtended\Definitions;
use Gmazzap\ModularityExtended\Event\ServiceResolved;
use Gmazzap\ModularityExtended\Event\BeforeServiceResolved;
use Gmazzap\ModularityExtended\Event\ServiceNotResolved;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class Container implements ContainerInterface
{
    public const ERROR_SERVICE_NOT_FOUND = 0;
    public const ERROR_SERVICE_DISABLED = 1;
    public const ERROR_SERVICE_UNMET_DEPENDENCY = 2;
    public const ERROR_SERVICE_FAILURE = 4;

    private EventDispatcherInterface $dispatcher;

    /**
     * @var array<string, mixed>
     */
    private array $cached = [];

    /**
     * @param Definitions $definitions
     * @return Container
     */
    public static function new(Definitions $definitions): Container
    {
        return new self($definitions);
    }

    /**
     * @param Definitions $definitions
     */
    private function __construct(private Definitions $definitions)
    {
        $this->dispatcher = $definitions->dispatcher();
    }

    /**
     * @param string $id
     * @return mixed
     */
    public function get(string $id): mixed
    {
        if (isset($this->cached[$id])) {
            return $this->cached[$id];
        }

        try {
            $info = $this->definitions->get($id);
            if (!$info) {
                $error = new class(
                    "Service '{$id}' not found.",
                    self::ERROR_SERVICE_NOT_FOUND
                ) extends \Error implements NotFoundExceptionInterface {};

                return $this->handleResolutionError($error, $id, null);
            }

            $event = new BeforeServiceResolved($info, $this);
            $this->dispatcher->dispatch($event);
            if (!$event->isServiceEnabled()) {
                $error = new class(
                    "Service '{$id}' was disabled by event.",
                    self::ERROR_SERVICE_DISABLED
                ) extends \Error implements ContainerExceptionInterface {};

                return $this->handleResolutionError($error, $id, $info);
            }

            foreach ($info->dependencies() as $dependency) {
                if (!$this->has($dependency)) {
                    $error = new class(
                        "Service {$id} require unsatisfied '{$dependency}'.",
                        self::ERROR_SERVICE_UNMET_DEPENDENCY
                    ) extends \Error implements ContainerExceptionInterface {};

                    return $this->handleResolutionError($error, $id, $info);
                }
            }

            $service = $info->definition()->define($this);
            $this->dispatcher->dispatch(new ServiceResolved($info, $service, $this));
            if ($info->isSingleton()) {
                $this->cached[$id] = $service;
            }
        } catch (\Throwable $error) {
            $error = new class(
                $error->getMessage(),
                self::ERROR_SERVICE_FAILURE,
                $error
            ) extends \Error implements ContainerExceptionInterface {};

            return $this->handleResolutionError($error, $id, $info ?? null);
        }

        return $service;
    }

    /**
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool
    {
        return $this->definitions->has($id);
    }

    /**
     * @param \Throwable $error
     * @param string $id
     * @param DefinitionInfo|null $info
     * @return mixed
     */
    private function handleResolutionError(
       \Throwable $error,
        string $id,
        DefinitionInfo|null $info
    ): mixed {

        $event = new ServiceNotResolved($id, $info, $error, $this);
        $this->dispatcher->dispatch($event);
        if ($event->hasService()) {
            return $event->service();
        }

        throw $error;
    }
}
