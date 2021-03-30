<?php

namespace NekoOs\ChameleonAccess\Providers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\PermissionRegistrar;

class Initialize extends ServiceProvider
{
    public function boot(PermissionRegistrar $permissionLoader, Filesystem $filesystem): void
    {
        if (function_exists('config_path')) { // function not available and 'publish' not relevant in Lumen
            $this->publishes([
                __DIR__ . '/../../database/migrations/create_groupings_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_groupings_table.php'),
                __DIR__ . '/../../database/migrations/create_model_groupings_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_model_groupings_table.php'),
            ], 'migrations');
        }
    }

    protected function getMigrationFileName(Filesystem $filesystem, $migrationFileName): string
    {
        $timestamp = date('Y_m_d_His');

        return Collection::make($this->app->databasePath() . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($migrationFileName, $filesystem) {
                return $filesystem->glob($path . "*_{$migrationFileName}");
            })->push($this->app->databasePath() . "/migrations/{$timestamp}_{$migrationFileName}")
            ->flatMap(function ($path) use ($filesystem, $migrationFileName) {
                return $filesystem->glob($path . '*_' . $migrationFileName);
            })
            ->push($this->app->databasePath() . "/migrations/{$timestamp}_{$migrationFileName}")
            ->first();
    }
}
