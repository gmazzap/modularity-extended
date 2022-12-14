# Modularity Extended

> An experiment to extend Inpsyde Modularity.
> Not for production.



## Service definition abstraction

Unlike Modularity, this package has an abstraction for service definition rather than relying
on "plain" callbacks. The interface looks like this:

```php
interface Definition
{
    public function id(): string;
    public function define(ContainerInterface $container): mixed;
    public function withPrevious(DefinitionInfo $previous): Definition;
}
```

The **`id()`** method is the ID of the service. In Modularity terms, that is the array _key_ used
in `ServiceModule::services()` method, for example.

The **`define()`** method is where the service construction happens. In Modularity terms, that is
the array _value_ used in `ServiceModule::services()` method, for example.

The **`withPrevious()`** method allows the definition object to "react" when a definition for the
same service (identified by `id()`) exists.
That happens on two occasions:

- When a service definition is overwriting a previously added definition
- When the definition is for an **extension**, and the "previous" definition given is for the
  service to extend.



## Extensions

The `Definition` interface does not provide any method to distinguish a "factory" from a "singleton",
or a service definition from an _extension_.
That information is provided by the `DefinitionInfo` class. We will get into the details of that
the class soon, but for now, let's say it "wraps" the `Definition` class and "extracts" information
from it based on **additional interfaces,** the `Definition` instance might implement or from
**PHP attributes**.

Let's assume a definition object like the following:

```php
class FooService implements Definition
{
    public function id(): string
    {
        return 'myapp.foo';
    }

    public function define(ContainerInterface $container): Foo
    {
        return new Foo($container->get('myapp.some-dependency'));
    }
    
    public function withPrevious(DefinitionInfo $previous): Definition
    {
        // This is not an extension. If this method is called, it means there was
        // already a definition for 'myapp.foo', but overwriting it is okay for us,
        // so we do nothing.
        return $this;
    }
}
```

Now we can write an extension for it:

```php
use Gmazzap\ModularityExtended\Definition\{Definition, Extension};
use Gmazzap\ModularityExtended\DefinitionInfo;

#[Extension]
class FooExtension implements Definition
{
    private DefinitionInfo|null $previous = null;
    
    public function id(): string
    {
        return 'myapp.foo';
    }

    public function define(ContainerInterface $container): DecoratedFoo
    {
        // This is an extension so `withPrevious()` should have been called by now,
        // unless something went horribly wrong.
        assert($this->previous !== null);
        
        $previous = $this->previous->definition()->define($container);
        
        return new DecoratedFoo($previous);
    }
    
    public function withPrevious(DefinitionInfo $previous): Definition
    {
        $this->previous = $previous;
        
        return $this;
    }
}
```

To be noted:

- We made the definition an extension via the **`#[Extension]`** attribute
- The service we extended was _not_ instantiated and passed to the extension's factory callback, but
  the factory callback instantiates the service it will extend. And that means
  the extension's factory callback can decide to discard the object and return a completely
  unrelated object.

### Extension by interface

Instead of using an attribute, a definition can be marked as an extension implementing the
`ExtensionAwareDefinition`, which extends `Definition` with an additional `isExtension()` method.

```php
use Gmazzap\ModularityExtended\Definition;

class FooExtension implements Definition\ExtensionAwareDefinition
{
    public function isExtension() : bool
    {
        return true;
    }
    /* ... omissis... */
}
```



## Singletons VS Factories

In Modularity, we have a distinction between "services" and "factories", where the former is
singletons, and the latter are services built every time they are retrieved from the container.

In this library, by default, all definitions are for "factories", and when a definition must be
treated as a singleton, it has to be marked with the `#[Sigleton]` attribute or
implement the `SingletonAwareDefinition` interface and its `isSingleton()` method.



## Dependencies

In Modularity, there's _no_ explicit support for service dependencies. This package allows
service definitions to explicitly declare other services they depend on.
That is done via a `#[Dependencies]` attribute or implementing the `DependencyAwareDefinition`
interface and its `dependencies()` method.

```php
use Gmazzap\ModularityExtended\Definition\{Definition, Dependencies};

#[Dependencies('myapp.bar', 'myapp.baz', 'myapp.and-so-on')]
class FooService implements Definition
{
    /* ... omissis... */
}
```

Right before the service is _resolved_, a lookup is done in the container for the services whose IDs
are set as dependencies. An exception is thrown if the container does not contain any of them.

That equals doing something like the following in Modularity:

```php
class FooModule implements \Inpsyde\Modularity\Module\ServiceModule
{
    public function services(): array {
        return [
           'myapp.foo' => function (\Psr\Container\ContainerInterface $c): Foo {
                if (!$c->has('myapp.bar') || !$c->has('myapp.baz')) {
                    throw new \Error('myapp.foo dependencies myapp.bar and myapp.baz not met.');
                }
                return new Foo();
           }
        ];
    }
}
```



## Metadata

Every definition might have metadata attached.
That is done via a `#[Meta]` attribute or implementing the `MetadataAwareDefinition` interface and its
`metadata()` method.

```php
use Gmazzap\ModularityExtended\Definition\{Definition, Meta};

#[Meta('description', 'This service is cool.')]
#[Meta('hint', 'The "Meta" attribute is repeatable.')]
class FooService implements Definition
{
    /* ... omissis... */
}
```



## Definition's info

The `DefintionInfo` class, which is, for example, passed to definitions' `withPrevious()` method,
is a gateway to all the definition's information. For example, all the information set via
attributes or additional interfaces is available via that class.

Moreover, `DefintionInfo` is capable of guessing (via PHP Reflections) the type of the service a
definition provides, without instantiating the service.

For example, to mention one of the things enabled by this capability, we could check in a
definition `withPrevious()` if we are _accidentally_ overriding a service:

```php
use Gmazzap\ModularityExtended\Definition\Definition;
use Gmazzap\ModularityExtended\DefinitionInfo;

class FooService implements Definition
{
    /* ... omissis... */
    public function withPrevious(DefinitionInfo $previous): Definition
    {
        if ($previous->serviceType() !== MyExpectedType::class) {
            throw new \Error('Oops, I was not supposed to replace this!');
        }
        return $this;
    }
}
```

### JSON representation

The `DefinitionInfo` class is JSON-serializable. Doing `json_serialize($info)` we can expect
a return value in the shape of:

```json
{
    "id": "myapp.foo",
    "serviceType": "MyApp\\Services\\Foo",
    "definitionClass": "MyApp\\Definitions\\FooService",
    "isSingleton": true,
    "isExtension": false,
    "dependencies": [
        "myapp.bar",
        "myapp.bar"
    ],
    "meta": {
        "description": "This is a very cool service"
    }
}
```



## Definition implementations

This library ships with two implementations of the `Definition` interface, one concrete and one
abstract.

### Abstract `BaseDefinition`

The abstract `BaseDefinition` class is useful for "quick" definitions using anonymous
classes and attributes. For example:

```php
use Gmazzap\ModularityExtended\Definition\{BaseDefinition, Meta, Singleton};

$fooDefinition = new
    #[Singleton, Meta('description', 'A quick definition')]
    class('myapp.foo') extends BaseDefinition
    {
        public function define(ContainerInterface $container): Foo {
            return new Foo($container->get());
        }
    }
```

### Concrete `CallableDefinition`

The `CallableDefinition` class is also helpful in declaring definitions without a custom class.
It implements all the "additional definition interfaces" and has setters to configure what
information methods like `metadata()` should return.

```php
use Gmazzap\ModularityExtended\Definition\CallableDefinition;

$fooDefinition = CallableDefinition::newSingleton(
    'myapp.foo',
    function(\Psr\Container\ContainerInterface $container): Foo {
        return new Foo($container->get());
    }
)->addMeta('description', 'A quick definition');
```

Moreover, it has three different named constructors: `newFactory()`, `newSingleton()`, and
`newExtension()` to build the three different types of definitions.
It's not a casualty those are also the three types of factory callbacks Modularity supports. In fact,
the `CallableDefinition` class is used internally to adapt any Modularity's module callback
definition in the format supported by this library.



## Adding definitions

Regardless of how definition objects are created, they must be added to the application.
The gateway for doing it is the `Definitions` object, which might be used _directly_ or _indirectly_
via the custom `Module` and `Package` classes this library provides.

The `Definitions` class' most relevant method is `add()`, used to add a definition instance.

```php
use Gmazzap\ModularityExtended\Definitions;

$definitions = Definitions::new();
$definitions->add(new FooService());
$definitions->add(new BarService());
```

The `add()` method returns the `DefinitionInfo` instance for the just-added definition, or `null` in
case of failure. (Failures might be caused by events, more on this soon).

### JSON representation

The `Definitions` class is JSON-serializable, and its JSON representation is nothing more than the
collection of the `DefintionsInfo` instances (JSON-serializable as well) for all added
definitions, including those that have been replaced.

Doing `json_serialize($definitions)`, we can expect a return value in the shape of:

```json
{
    "myapp.foo": [
        {
            "id": "myapp.foo",
            "serviceType": "MyApp\\Services\\Foo",
            "definitionClass": "MyApp\\Definitions\\FooService",
            "isSingleton": true,
            "isExtension": false,
            "dependencies": [
                "myapp.bar",
                "myapp.bar"
            ],
            "meta": {
                "description": "This is a very cool service"
            }
        },
        {
            "id": "myapp.foo",
            "serviceType": "MyApp\\Services\\DecoratedFoo",
            "definitionClass": "MyApp\\Definitions\\FooExtension",
            "isSingleton": true,
            "isExtension": true,
            "dependencies": [
                "myapp.bar",
                "myapp.bar"
            ],
            "meta": {
                "description": "This is the extension of myapp.foo"
            }
        }
    ]
}
```



## Definitions Module

Modularity provides a base `Module` interface that this library extends to introduce a
`DefintionsModule` interface is supposed to provide definitions instances.

```php
use Gmazzap\ModularityExtended\Module;
use Gmazzap\ModularityExtended\Definition;

class MyAppModule implements Module
{
    use \Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
    
    public function definitions(): array
    {
        return [
            new Defintions\FooService(),  
                 
            new Defintions\BarService(),   
                
            new Defintions\BazService(),
               
            new
            #[Definition\Singleton] 
            #[Definition\Dependencies('myapp.foo', 'myapp.bar')]   
            #[Definition\Meta('description', 'Definitions via anon class are cool.')]
            class('myapp.meh') extends Definition\BaseDefinition
            {
                public function define(ContainerInterface $container): mixed
                {
                    return new Services\Meh();
                }
            },       
        ];
    }
}
```



## Extended Package

The definitions' module class described above would not work if passed to the Modularity's `Package`,
which would not recognize it.

That is why this library introduces a custom `Package` class (extending Modularity's `Package`)
that can work with definitions modules.

It works pretty much identically to Modularity's `Package`:

```php
use Gmazzap\ModularityExtended\Package;
use Inpsyde\Modularity\Properties\PluginProperties;

Package::new(PluginProperties::new(__FILE__))
    ->addModule(new DefinitionsModuleOne())
    ->addModule(new DefinitionsModuleTwo())
    ->boot();
```

The `Definitions` classes that are used behind the scenes to collect the definitions provided by
the modules are instantiated behind the scenes when doing `Package::new()`.

It is also possible to instantiate it explicitly:

```php
use Gmazzap\ModularityExtended\{Package, Definitions};
use Inpsyde\Modularity\Properties\PluginProperties;

// we can add definitions to this instance in any way we like
$definitions = Definitions::new();

Package::newWithDefinitions(PluginProperties::new(__FILE__), $definitions)
    ->addModule(new DefinitionsModuleOne())
    ->addModule(new DefinitionsModuleTwo())
    ->boot();
```

In any case, if we hold an instance of `Package`, we can call its `definitions()` method to
obtain the `Definitions` instance embedded into it.

Considering `Definitions` is JSON-serialize, we could super-easily obtain information about the app's
definitions.

Just for fun, let's see how easy it is to write a REST endpoint that prints all the info about the
added dependencies:

```php
namespace MyApp;

use Gmazzap\ModularityExtended\{Package, Definition};
use Inpsyde\Modularity\Module\{ExecutableModule, ModuleClassNameIdTrait};
use Inpsyde\Modularity\Properties\PluginProperties;
use Psr\Container\ContainerInterface;

function plugin(): Package {
    static $package;
    $package or $package = Package::new(PluginProperties::new(__FILE__));
    
    return $package;
}

class DefinitionsEndpointModule implements ExecutableModule
{
    use ModuleClassNameIdTrait;
    
    public function run(ContainerInterface $container): bool
    {
        return add_action('rest_api_init', function () {
            register_rest_route('myapp/v1', '/definitions', [
                 'methods' => 'GET',    
                 'permission_callback' => 'is_user_logged_in',
                 'callback' => static fn() => plugin()->definitions(),    
            ]);
        });
    }
}

plugin()
    ->addModule(new DefinitionsModuleOne())
    ->addModule(new DefinitionsModuleTwo())
    // ...
    ->boot(new DefinitionsEndpointModule());
```



## PSR-14 events

This library requires the PSR-14 standard interfaces. It also provides a minimal/noop event
dispatcher implementation that works as a "placeholder" for more meaningful implementation
that consumers can require an inject into the library's `Package` via its `withDispatcher()`
method.

The library emits [a lot of events](./src/Event) that listeners can use for various purposes,
e.g., to listen to service definitions added, replaced, or services resolved, extended, etc.

Most of the emitted events are "informational", but a few of them might interact with the
application flow.

For example, it is possible to listen to an event and prevents a service definition addition or a
service resolution in the container, or even react to a service resolution failure due to an
exception and "recover" the service (or maybe just log the failure).

### Only for this library services and modules

All the events this library emits are related to services, modules, and service definitions that
embrace this library's way of declaring dependencies. While "core" Modularity modules and services
are 100% supported, there will be no PSR-14 events for them.



## Advanced extension topics

### Extend definitions, not services

In Modularity, we define callbacks to extend services. This library promotes the extension
of the _service's definition_ more than the service itself.
Thanks to that, we can have more control over how to extend the service (or to extend at all),
Even before the service is instantiated.

### Extensions are type-safe

Another thing to consider is that in Modularity, because we extend _services,_ all added extensions
are _always_ executed, and the resulting service is passed to the next extension, possibly
breaking their argument type-declaration.
In this library, that can be avoided in two ways:

- in the extension, we can instantiate the "previous" object and check its type, and if it is not
  the type expect, we could discard it and return something from scratch, or we could decide not to
  apply any extension at all.
- in the extension, even before instantiating the "previous" object, we can check the service type
  leveraging `DefintionInfo::serviceType()`, which uses Reflections to tell the return value of
  the definition's `define()` method.

### Extensions are more powerful

This library's approach also provides a way to change the _nature_ of the service. For example,
if the service was initially registered as a factory, by default, it stays a factory after
the extensions are applied.
But we might want to change that and turn a singleton into a factory or the other way around.

To ensure a service is a singleton after an extension is applied, regardless of how it was
registered, we can mark the extension's definition as a singleton.

For the opposite behavior, just not marking the extension as a singleton won't serve us well,
because the logic of extending definitions **merges** the original service's definition with the
extension's definition, if _one of the two_ is a singleton, the result is a singleton.
To change that, we must change how "merging" logic is applied.
We can do that via the `#[MergeStrategy]` attribute.

For example:

```php
use Gmazzap\ModularityExtended\Definition\{Definition, MergeStrategy, Extension, Singleton};
use Gmazzap\ModularityExtended\DefinitionInfo;

#[Singleton]
class FooService implements Definition
{
    public function id(): string
    {
        return 'myapp.foo';
    }
    /* ... omissis... */
}

#[Extension, MergeStrategy(MergeStrategy::MERGE_META)]
class FooExtension implements Definition\Definition
{
    public function id(): string
    {
        return 'myapp.foo';
    }
    /* ... omissis... */
}
```

In the snippet above, `FooService` is marked as a singleton, and `FooExtension` is its extension, but
via the `#[MergeStrategy]` attribute, we are telling the library only to merge metadata,
that is, the resulting definition will have metadata from both the original service and the
extension. Because it will not merge singleton information, the resulting service will not be a
singleton.

By default, the merged characteristics are:

- "singleton" controlled via the `MergeStrategy::MERGE_SINGLETON` flag
- "metadata" controlled via the `MergeStrategy::MERGE_META` flag
- "dependencies" controlled via the `MergeStrategy::MERGE_DEPENDENCIES` flag.

Via the `#[MergeStrategy]` attribute, it is possible to decide which property should be merged.
Multiple flags can be used as bitmask, like
`#[MergeStrategy(MergeStrategy::MERGE_META|MergeStrategy::MERGE_DEPENDENCIES)]`, or it is
possible to use `#[MergeStrategy(MergeStrategy::MERGE_NONE)]` to merge nothing.
For completeness' sake, `#[MergeStrategy(MergeStrategy::MERGE_ALL)]` is also available, even if
it is not very useful considering merging all the information is the default behavior.



## Advanced `Package` topics

### 100% Modularity compatible

The custom `Package` class is 100% compatible with Modularity's `Package`. We can add to it
any module that uses Modularity's `Module` interfaces, just like we can write modules that
implements _both_ Modularity's `Module` interfaces and this library's `Module` interface.

### External containers supported

When we described this library's `Package` class above, we mentioned the `Package::new()` method.

Its signature is identical to Modularity's `Package::new()`, which means it accepts a variadic
number of PSR-11 containers, which will work as expected.

### About `Package::newWithCompiler()`

The `Package::newWithCompiler()` is available as well in this library's `Package`. However, it
only accepts an instance of this library's `ContainerCompiler` implementation, which is normally
used behind the scenes, and it makes this library possible at all.

So we can do the following:

```php
use Gmazzap\ModularityExtended\{Package, Container, Definitions};
use Inpsyde\Modularity\Properties\PluginProperties;

$compiler = Container\ContainerCompiler::new(Definitions::new());
Package::newWithCompiler(PluginProperties::new(__FILE__), $compiler)
    ->addModule(new DefinitionsModuleOne())
    ->addModule(new DefinitionsModuleTwo())
    ->boot();
```

But there would be little benefit in doing that. If manual instantiation of `Definitions` is needed,
e.g., to add definitions outside the `Package`, the `Package::newWithDefinitions()` method will
probably make more sense. Unless one wants to _extend_ the default `ContainerCompiler` shipped
with the library.
