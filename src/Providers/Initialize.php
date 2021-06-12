<?php

namespace NekoOs\Laravel\Permission\Providers;

use NekoOs\Laravel\Permission\Loader;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\PermissionServiceProvider;

class Initialize extends PermissionServiceProvider
{

    public function boot(PermissionRegistrar $permissionLoader)
    {
        parent::boot($permissionLoader);

        if (function_exists('config_path')) { // function not available and 'publish' not relevant in Lumen

            $this->publishes([
                __DIR__ . '/../../database/migrations/create_groupings_table.php.stub'       => $this->getMigrationFileName('create_groupings_table.php'),
                __DIR__ . '/../../database/migrations/create_model_groupings_table.php.stub' => $this->getMigrationFileName('create_model_groupings_table.php'),
            ], 'migrations');
        }

        $this->app->make(Loader::class)->register();
    }
}
