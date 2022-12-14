<?php

declare(strict_types=1);

namespace Gmazzap\ModularityExtended;

use Gmazzap\ModularityExtended\Container\ContainerCompiler;
use Gmazzap\ModularityExtended\Event\ModuleAdded;
use Gmazzap\ModularityExtended\Event\ServiceAdded;
use Gmazzap\ModularityExtended\Event\BeforeModuleAdded;
use Gmazzap\ModularityExtended\Event\ModuleNotAdded;
use Gmazzap\ModularityExtended\Event\ServiceNotAdded;
use Inpsyde\Modularity\Container\ContainerCompiler as ModularityCompiler;
use Inpsyde\Modularity\Module\Module as ModularityModule;
use Inpsyde\Modularity\Package as ModularityPackage;
use Inpsyde\Modularity\Properties\Properties;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class Package extends ModularityPackage
{
    public const MODULE_REGISTERED_DEFINITIONS = 'registered-definitions';

    private Definitions $definitions;
    private EventDispatcherInterface $dispatcher;

    /**
     * @param Properties $properties
     * @param Definitions $definitions
     * @return ModularityPackage
     */
    public static function newWithDefinitions(
        Properties $properties,
        Definitions $definitions
    ): ModularityPackage {

        return new self($properties, ContainerCompiler::new($definitions));
    }

    /**
     * @param Properties $properties
     * @param ContainerInterface ...$containers
     * @return ModularityPackage
     */
    public static function new(
        Properties $properties,
        ContainerInterface ...$containers
    ): ModularityPackage {

        $compiler = ContainerCompiler::new(Definitions::new());
        array_map([$compiler, 'addContainer'], $containers);

        return new self($properties, $compiler);
    }

    /**
     * @param Properties $properties
     * @param ModularityCompiler $containerCompiler
     * @return Package
     */
    public static function newWithCompiler(
        Properties $properties,
        ModularityCompiler $containerCompiler
    ): Package {

        if (!($containerCompiler instanceof ContainerCompiler)) {
            throw new \TypeError(
                sprintf(
                    '%s() require an instance of %s, %s provided.',
                    __METHOD__,
                    ContainerCompiler::class,
                    get_class($containerCompiler)
                )
            );
        }

        return new self($properties, $containerCompiler);
    }

    /**
     * @param Properties $properties
     * @param ModularityCompiler $containerCompiler
     */
    protected function __construct(
        Properties $properties,
        ModularityCompiler $containerCompiler
    ) {
        if ($containerCompiler instanceof ContainerCompiler) {
            $this->definitions = $containerCompiler->definitions();
            $this->dispatcher = $this->definitions->dispatcher();

            parent::__construct($properties, $containerCompiler);

            return;
        }

        $this->definitions = Definitions::new();
        $this->dispatcher = $this->definitions->dispatcher();

        parent::__construct($properties, $containerCompiler);
    }

    /**
     * @param EventDispatcherInterface $dispatcher
     * @return static
     */
    public function withDispatcher(EventDispatcherInterface $dispatcher): static
    {
        $this->dispatcher = $dispatcher;
        $this->definitions->withDispatcher($dispatcher);

        return $this;
    }

    /**
     * @param ModularityModule $module
     * @return static
     */
    public function addModule(ModularityModule $module): ModularityPackage
    {
        parent::addModule($module);

        if (!$module instanceof Module) {
            return $this;
        }

        $moduleId = $module->id();
        $wasAdded = $this->moduleIs($moduleId, self::MODULE_ADDED);

        $event = new BeforeModuleAdded($moduleId, $this->properties);
        $this->dispatcher->dispatch($event);
        if (!$event->isModuleAllowed()) {
            $this->dispatcher->dispatch(new ModuleNotAdded($moduleId, $this->properties));

            return $this;
        }

        $serviceIds = [];
        foreach ($module->definitions() as $definition) {
            $info = $this->definitions->add($definition);
            if (!$info) {
                $event = new ServiceNotAdded($definition, $moduleId, $this->properties);
                $this->dispatcher->dispatch($event);
                continue;
            }
            $serviceIds[] = $definition->id();
            $this->dispatcher->dispatch(new ServiceAdded($info, $moduleId, $this->properties));
        }

        $this->extendedModuleProgress($moduleId, $serviceIds, $wasAdded);
        $this->dispatcher->dispatch(new ModuleAdded($moduleId, $serviceIds, $this->properties));

        return $this;
    }

    /**
     * @return Definitions
     */
    public function definitions(): Definitions
    {
        return $this->definitions;
    }

    /**
     * @param string $moduleId
     * @param list<string> $services
     * @param bool $wasAdded
     * @return void
     */
    private function extendedModuleProgress(string $moduleId, array $services, bool $wasAdded): void
    {
        $messageFmt = '%s %s via extended package.';

        if (!$services) {
            $status = self::MODULE_NOT_ADDED;
            $this->moduleStatus[self::MODULES_ALL][] = sprintf($messageFmt, $moduleId, $status);

            return;
        }

        $progressType = self::MODULE_REGISTERED_DEFINITIONS;
        if (!isset($this->moduleStatus[$progressType])) {
            $this->moduleStatus[$progressType] = [];
        }
        $this->moduleStatus[$progressType][] = $moduleId;

        if (!isset($this->moduleStatus[self::MODULES_ALL])) {
            $this->moduleStatus[self::MODULES_ALL] = [];
        }

        $description = $this->properties->isDebug()
            ? sprintf('%s %s (%s)', $moduleId, $progressType, implode(', ', $services))
            : sprintf('%s %s', $moduleId, $progressType);
        $this->moduleStatus[self::MODULES_ALL][] = $description;

        if (!$wasAdded) {
            if (isset($this->moduleStatus[self::MODULE_NOT_ADDED])) {
                $notAddedNow = $this->moduleStatus[self::MODULE_NOT_ADDED];
                $notAdded = array_values(array_diff($notAddedNow, [$moduleId]));
                $this->moduleStatus[self::MODULE_NOT_ADDED] = $notAdded;
            }
            if (!isset($this->moduleStatus[self::MODULE_ADDED])) {
                $this->moduleStatus[self::MODULE_ADDED] = [];
            }
            $this->moduleStatus[self::MODULE_ADDED][] = $moduleId;
        }

        $status = self::MODULE_ADDED;
        $this->moduleStatus[self::MODULES_ALL][] = sprintf($messageFmt, $moduleId, $status);
    }
}
