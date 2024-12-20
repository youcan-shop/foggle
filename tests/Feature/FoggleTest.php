<?php

use Workbench\App\Models\Cat;
use Workbench\App\Models\User;
use YouCanShop\Foggle\FogGen;

use function Orchestra\Testbench\workbench_path;

it('discovers features', function () {
    $foggle = $this->foggle();

    $foggle->discover(
        'Workbench\\App\\Features',
        workbench_path('app/Features')
    );

    expect($foggle->defined())->toBeGreaterThan(0);
});

it('retrieves a feature\'s value by name', function () {
    $foggle = $this->foggle();

    $foggle->discover(
        'Workbench\\App\\Features',
        workbench_path('app/Features')
    );

    expect($foggle->get('always-true', null))->toBeTrue();
});

it('considers undefined features false', function () {
    expect(foggle()->get('non-existent-feature', null))->toBeFalse();
});

it('resolves with a context', function () {
    foggle()->discover(
        'Workbench\\App\\Features',
        workbench_path('app/Features')
    );

    expect(foggle()->for('true')->active('resolves-to-context'))->toBeTrue()
        ->and(foggle()->for('false')->active('resolves-to-context'))->toBeFalse();
});

it('splodes properly', function () {
    expect(splode(config('features.billing.sellers')))->toEqual([1, 2, 3]);
});

it('registers a generated feature', function () {
    foggle()->define('billing', FogGen::inconfig('features.billing.sellers'));

    expect(foggle()->for('1')->active('billing'))->toBeTrue()
        ->and(foggle()->for('0')->active('billing'))->toBeFalse();
});

it('resolves a foggable using its id', function () {
    foggle()->define('billing', FogGen::inconfig('features.billing.sellers'));

    expect(foggle()->for(new User('1'))->active('billing'))->toBeTrue()
        ->and(foggle()->for(new User('0'))->active('billing'))->toBeFalse();
});

it('resolves context for classes', function () {
    foggle()->discover(
        'Workbench\\App\\Features',
        workbench_path('app/Features')
    );

    expect(foggle()->active('allow-number-seven'))->toBeTrue()
        ->and(foggle()->for(new Cat('8'))->active('allow-number-seven'))->toBeFalse();
});

it('resolves context for callables', function () {
    foggle()->define(
        'allow-number-seven',
        fn(Cat $cat) => $cat->foggleId() === '7',
        Cat::class
    );

    expect(foggle()->active('allow-number-seven'))->toBeTrue()
        ->and(foggle()->for(new Cat('8'))->active('allow-number-seven'))->toBeFalse();
});

it('resolves context for generations', function () {
    foggle()->define(
        'allow-number-seven',
        FogGen::inconfig('features.allow-number-seven'),
        Cat::class,
    );

    expect(foggle()->active('allow-number-seven'))->toBeTrue()
        ->and(foggle()->for(new Cat('8'))->active('allow-number-seven'))->toBeFalse();
});

it('purges a feature', function () {
    foggle()->define(
        'allow-number-seven',
        fn() => true,
        Cat::class
    );

    expect(foggle()->active('allow-number-seven'))->toBeTrue();

    foggle()->define('allow-number-seven', fn () => false, Cat::class);

    expect(foggle()->active('allow-number-seven'))->toBeTrue();

    foggle()->purge(['allow-number-seven']);

    expect(foggle()->active('allow-number-seven'))->toBeFalse();
});
