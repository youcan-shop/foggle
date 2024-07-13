<?php

namespace YouCanShop\Foggle\Commands;

use Illuminate\Console\Command;
use YouCanShop\Foggle\Foggle;

class PurgeCommand extends Command
{
    protected $signature = 'foggle:purge {features?*} {--except=*} {--except-registered} {--store=}';
    protected $description = 'Deletes feature resolutions from storage';

    public function handle(Foggle $foggle): int
    {
        $store = $foggle->store($this->option('store'));
        $features = $this->argument('features') ?? null;

        $except = collect($this->option('except'))
            ->when(
                $this->option('except-registered'),
                fn($except) => $except->merge($store->defined())
            )
            ->unique()
            ->all();

        if ($except) {
            $features = collect($features ?: $store->stored())
                ->flip()
                ->forget($except)
                ->flip()
                ->values()
                ->all();
        }

        $store->purge($features);

        with($features ?: ['All features'], function ($names) {
            $this->components->info(implode(', ', $names) . ' successfully purged from storage.');
        });

        return self::SUCCESS;
    }
}
