<?php

use Workbench\App\Features\AlwaysTrue;

it('resolves a feature', function () {
    $foggle = $this->foggle();

    expect($foggle->feature(AlwaysTrue::class))->toBeTrue();
});
