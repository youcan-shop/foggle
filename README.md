# Foggle

A feature flagging package for Laravel, built with DX in mind.

> [!NOTE]
> Some features are not yet implemented:
>   - Feature resolutions purge command.
>   - Pre-resolution hook for global flagging.

## Installation

Install Foggle into your project using composer:

```shell
composer require youcan-shop/foggle
```

You should then publish yhe configuration files using the following artisan command:

```shell
php artisan vendor:publish --provider="YouCanShop\Foggle\FoggleServiceProvider"
```

## Configuration

After publishing, the configuration file will be located at `config/foggle.php`. This is where you configure your storage providers and context resolvers.
Foggle allows you to store the resolution of your feature flags in a vast array (haha) of data stores, or in an in-memory `array` driver.

## Feature Definition

To define a feature, you can use the `define` method of the `foggle()` helper. You will need to provide a name of the feature, as well as a closure that resolves the initial value.

Usually, a feature should be defined in a dedicated service provider. The closure will receive the `context` for the resolution as an argument, which is most commonly your application's `User` model.

```php
<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
  public function boot(): void
  {
    foggle()->define('themes', fn (User $user) => match (true) {
      $user->isTeamMember() => true,
      $user->isTestUser() => false,
      default => false,
    });
  }
}
```
The first time the `themes` feature is resolved for a given context (user), the result will be stored by your configured driver. The next time it is checked against the same context, the closure will not be invoked, and the value will be retrieved from the storage.

## Class Based Features & Discovery

Foggle allows you to define class based features. These classes can be automatically discovered and registered into the feature manager. By default, the auto-discovery path is `app/Features` but this can be changed from the config file.

When writing a feature class, you need to define a `resolve` or `__invoke` method, which will be called to resolve the feature's initial value. In this case, the class' FQN will be used as a feature name, but you can override it by defining a public `name` method on your feature class.

```php
<?php
 
namespace App\Features;
 
use App\Models\User;
use Illuminate\Support\Lottery;
 
class NewApi
{
    public function resolve(User $user): mixed
    {
        return match (true) {
            $user->isInternalTeamMember() => true,
            $user->isHighTrafficCustomer() => false,
            default => Lottery::odds(1 / 100),
        };
    }
}
```

## Checking Features

To evaluate a feature's value, you may use the `active` method on the `foggle()` helper.

```php
<?php

function are_themes_active() {
  return foggle()->active('themes');
}
```

If you need to check a feature against a specific context, you can prepend the feature resolution call call with a `for` method like so:

```php
<?php

function are_themes_inactive(User $user) {
  return foggle()->for($user)->inactive('themes');
}
```


## In-Memory Cache

When resolving a feature, Foggle will create an in-memory cache of the result. If you are using the `redis` driver for example, this means that re-checking hte same feature within the lifetime of a single request will not trigger additional Redis queries. 

If you need to manually flush the in-memory cache, you can use the `cFlush` method on the `foggle()` helper.
Note that when running in console, in-memory cache is flushed every time a job is processed to ensure long-running worker processes will always have the latest values.

```php
foggle()->cFlush()
```

## Context

### Resolvers

As mentioned before, Foggle allows you to check your features against any context using the `for` method on `foggle()`. However, if you would like to omit the `for` every time you check a feature, you can configure custom context resolvers in the config file.

You can do so by creating a class that implements `ContextResolver` like so:

```php
<?php

namespace App\ContextResolvers;

use App\Services\StoreService;
use YouCanShop\Foggle\Contracts\ContextResolver;

class StoreResolver implements ContextResolver {
  public function __construct(private readonly StoreService $storeService)
  {
  }

  public function resolve(): ?Store
  {
    return $this->storeService->getCurrentStore();
  }
}
```

You should then bind this class to the type of context it resolves, which is `Store` in this case, in the config file:

```php
<?php

return [
  // ...

  'context_resolvers' => [
    Store::class => StoreResolver::class,
  ]

];
```

### Defining A Feature's Context Type

By default, if you do not provide context to a feature, it will not try to use a context resolver and will default to null. You can tell Foggle which resolver to use in the feature definition in one of two ways:
- When using class based features, you must define a public `$contextType` property containing the context's class name.
- When using closure based features, or `FogGen` generations, you can declare your context type as a 3rd parameter to the `define` method on `foggle()`.

### Context Identifiers

Foggle's built-in drivers will store your context alongside the resolution value in their stores. However, serializing some contexts as-is can be heavy on some data stores (e.g. Redis) which is why every context that isn't a string must implement the `Foggable` interface.

```php
<?php
 
namespace App\Models;
 
use FlagRocket\FlagRocketUser;
use Illuminate\Database\Eloquent\Model;
use YouCanShop\Foggle\Contracts\Foggable;
 
class User extends Model implements Foggable
{
    public function foggleId(): string
    {
        return $this->id;
    }
}
```

## Feature generation

Some features are often too simple to warrant an entire class or a repeated closure on each definition, which is why Foggle provides `FogGen` class that generates common feature closures.

### FogGen::inconfig()

The `inconfig` method takes a config path and generates a feature that checks if the given context's identifier is included in the provided config's value. If the config value is a string, `inconfig` attempts to explode it into an array using the `,` separator by default, which can be changed using its optional 2nd param.

```php
<?php

foggle()->define('billing-v2', FogGen::inconfig('features.billing-v2.stores', ','), Store::class);

```

In this case, it will check if the store's `id` attribute exists within the `features.billing-v2.stores` value.
