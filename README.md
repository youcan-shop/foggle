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

  // The feature defines its parameters here, can be anything
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


