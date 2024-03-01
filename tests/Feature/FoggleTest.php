<?php

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

it('resolves with a context', function () {
    foggle()->discover(
        'Workbench\\App\\Features',
        workbench_path('app/Features')
    );

    expect(foggle()->for(true)->active('resolves-to-context'))->toBeTrue()
        ->and(foggle()->for(false)->active('resolves-to-context'))->toBeFalse();
});
