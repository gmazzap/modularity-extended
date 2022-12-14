<?php

declare(strict_types=1);

namespace Gmazzap\ModularityExtended;

use Gmazzap\ModularityExtended\Definition\CallableDefinition;
use Gmazzap\ModularityExtended\Definition\Dependencies;
use Gmazzap\ModularityExtended\Definition\DependencyAwareDefinition;
use Gmazzap\ModularityExtended\Definition\Extension;
use Gmazzap\ModularityExtended\Definition\ExtensionAwareDefinition;
use Gmazzap\ModularityExtended\Definition\MergeStrategy;
use Gmazzap\ModularityExtended\Definition\Meta;
use Gmazzap\ModularityExtended\Definition\MetadataAwareDefinition;
use Gmazzap\ModularityExtended\Definition\Definition;
use Gmazzap\ModularityExtended\Definition\Singleton;
use Gmazzap\ModularityExtended\Definition\SingletonAwareDefinition;

final class DefinitionInfo implements \JsonSerializable
{
    /** @var \ReflectionClass<Definition>|null  */
    private \ReflectionClass|null $reflection = null;
    /** @var array<string, mixed>|null */
    private array|null $metadata = null;
    /** @var list<string>|null */
    private array|null $dependencies = null;
    private bool|null $isSingleton = null;
    private bool|null $isExtension = null;
    private string|null $type = null;

    /**
     * @param Definition $definition
     * @return DefinitionInfo
     */
    public static function new(Definition $definition): DefinitionInfo
    {
        return new self($definition);
    }

    /**
     * @param DefinitionInfo $info
     * @param DefinitionInfo $previous
     * @return DefinitionInfo
     */
    public static function merge(DefinitionInfo $info, DefinitionInfo $previous): DefinitionInfo
    {
        $instance = static::replace($info, $previous);
        if (!$info->isExtension()) {
            return $instance;
        }

        $strategy = static::mergeStrategy($info);
        if ($strategy->mergeSingleton()) {
            $instance->isSingleton = $info->isSingleton() || $previous->isSingleton();
        }
        if ($strategy->mergeDependencies()) {
            $instance->dependencies = array_merge($previous->dependencies(), $info->dependencies());
        }
        if ($strategy->mergeMeta()) {
            $instance->metadata = array_merge($previous->metadata(), $info->metadata());
        }

        return $instance;
    }

    /**
     * @param DefinitionInfo $info
     * @param DefinitionInfo $previous
     * @return DefinitionInfo
     */
    public static function replace(DefinitionInfo $info, DefinitionInfo $previous): DefinitionInfo
    {
        $instance = static::new($info->definition->withPrevious($previous));
        $instance->isExtension = false;

        return $instance;
    }

    /**
     * @param DefinitionInfo $info
     * @return MergeStrategy
     */
    private static function mergeStrategy(DefinitionInfo $info): MergeStrategy
    {
        $strategy = null;
        $mergeAttr = $info->attribute(MergeStrategy::class);
        foreach ($mergeAttr as $attribute) {
            $strategy = $attribute->newInstance();
            break;
        }

        return $strategy ?? new MergeStrategy(MergeStrategy::MERGE_ALL);
    }

    /**
     * @param Definition $definition
     */
    private function __construct(private Definition $definition)
    {
    }

    /**
     * @return Definition
     */
    public function definition(): Definition
    {
        return $this->definition;
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return $this->definition->id();
    }

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        if ($this->metadata === null) {
            $this->metadata = [];
            if ($this->definition instanceof MetadataAwareDefinition) {
                $this->metadata = $this->definition->metadata();
            }
            $attributes = $this->attribute(Meta::class);
            foreach ($attributes as $attribute) {
                $meta = $attribute->newInstance();
                $this->metadata[$meta->key()] = $meta->value();
            }
        }

        return $this->metadata;
    }

    /**
     * @return list<string>
     */
    public function dependencies(): array
    {
        if (($this->dependencies === null)) {
            $this->dependencies = [];
            if ($this->definition instanceof DependencyAwareDefinition) {
                $this->dependencies = $this->definition->dependencies();
            }
            $attributes = $this->attribute(Dependencies::class);
            foreach ($attributes as $attribute) {
                $attrDependencies = $attribute->newInstance()->dependencies();
                $this->dependencies = array_merge($this->dependencies, $attrDependencies);
                break;
            }
        }

        return $this->dependencies;
    }

    /**
     * @return bool
     */
    public function isSingleton(): bool
    {
        if ($this->isSingleton === null) {
            if ($this->definition instanceof SingletonAwareDefinition) {
                $this->isSingleton = $this->definition->isSingleton();
            }
            $this->isSingleton = $this->isSingleton ?? (bool)$this->attribute(Singleton::class);
        }

        return $this->isSingleton;
    }

    /**
     * @return bool
     */
    public function isExtension(): bool
    {
        if ($this->isExtension === null) {
            if ($this->definition instanceof ExtensionAwareDefinition) {
                $this->isExtension = $this->definition->isExtension();
            }
            $this->isExtension = $this->isExtension ?? (bool)$this->attribute(Extension::class);
        }

        return $this->isExtension;
    }

    /**
     * @return string
     */
    public function serviceType(): string
    {
        if ($this->type === null) {
            $methodRef = ($this->definition instanceof CallableDefinition)
                ? new \ReflectionFunction(\Closure::fromCallable($this->definition->factory()))
                : $this->defReflection()->getMethod('define');
            $type = $methodRef->getReturnType();
            $this->type = $type ? (string)$type : 'mixed';
        }

        return $this->type;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id(),
            'serviceType' => $this->serviceType(),
            'definitionClass' => get_class($this->definition),
            'isSingleton' => $this->isSingleton(),
            'isExtension' => $this->isExtension(),
            'dependencies' => $this->dependencies(),
            'meta' => $this->metadata(),
        ];
    }

    /**
     * @template T of object
     * @param class-string<T> $name
     * @return array<\ReflectionAttribute<T>>
     */
    private function attribute(string $name): array
    {
        return $this->defReflection()->getAttributes($name, \ReflectionAttribute::IS_INSTANCEOF);
    }

    /**
     * @return \ReflectionClass<Definition>
     */
    private function defReflection(): \ReflectionClass
    {
        $this->reflection or $this->reflection = new \ReflectionClass($this->definition);

        return $this->reflection;
    }
}
