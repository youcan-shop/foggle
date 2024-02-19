# Foggle

A feature flagging package for Laravel.

## Usage

> [!WARNING]
> Note that this usage section is a proposed spec, the implementation is a work in progress.

#### Defining features

Features are defined as classes. The package will load any feature defined in the `App\Features\` namespace by default, but can be customized.

```php
namespace App\Features;

class Themes {
  public string $name = 'themes';

  public function __construct(private readonly Config $config)
  {
    // the feature is instantiated through the container
    // meaning it can inject any dependencies it needs
  }

  // The feature defines its context here, can be anything
  // The return type is also customizable

  public function resolve(Seller $seller): bool
  {
    if ($seller->isInternalTeamMember()) {
      return true;
    }

    // Note that at this iteration, the package will not manage configuration values for each feature.

    $flaggedSellers = explode($this->config->get('features.themes.seller'), ', ');

    return in_array($seller->getId(), $flaggedSellers);
  }
}
```

The minimum requirements for a feature class are:
- A public `resolve()` method that returns a mixed value.
- A public string `$name` property that is used to identify the feature.

These cannot be enforced through an interface, and will be validated in the registration phase of the applicaton.

Note that 

#### Resolving features

Features are lazily resolved by default. To check whether a feature is active, you can use the Foggle feature manager like so:

```php
// Returns a boolean
make(Foggle::class)->for($seller)->active('themes');

// Returns the raw resolution
make(Foggle::class)->for($seller)->value('themes');

// you can alternatively use the helper function
foggle()->for($seller)->active('themes');
```

Note that `values()` will always return the result of the feature's `resolve()` method, but `active()` will return true if the value evaluates as truthy.

It is also possible to use a middleware to check a feature before the request reaches a controller:

```php
Route::name('themes.index')->get('/themes', ThemesIndexController::class)->middleware(['foggle:themes,themes.install']);
```

Depending on the configured driver, Foggle will also persist the result of every resolution and re-use it in future requests. This can be particularly useful when defining features that don't depend on a state, for example the themes feature would have a 50% chance of activating at random, instead of persisting that state manually and checking every time the feature resolves, Foggle does that for you.

#### Context resolvers

Instead of providing the context each time using `for()`, you can define custom context resolvers that are used when a context is not explicitly defined. For example, to use the current authenticated seller for any feature that uses a Seller entity as the context, you can do the following:

```php
// AppServiceProvider.php

foggle()->resolveContextUsing(Seller::class, fn () => auth()->user());
```





