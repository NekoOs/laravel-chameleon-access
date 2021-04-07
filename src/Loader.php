<?php

namespace NekoOs\ChameleonAccess;

use Closure;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Foundation\Application;
use NekoOs\ChameleonAccess\Concerns\HasScopes;
use NekoOs\ChameleonAccess\Contracts\Scope;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class Loader
{

    private $app;

    private $gate;

    public function __construct(Application $app, Gate $gate)
    {
        $this->app = $app;
        $this->gate = $gate;
    }

    public function register(): bool
    {
        $this->gate->before(Closure::fromCallable([$this, 'hasPermissionTo']));
        $this->app->bind(Scope::class, function () {
            return null;
        });

        return true;
    }

    /**
     * @param Authorizable|HasScopes $user
     * @param string                 $ability
     *
     * @return mixed
     */
    protected function hasPermissionTo(Authorizable $user, string $ability)
    {
        try {
            if (($scope = $user->resolveCurrentScope()) && method_exists($user, 'withScopeHasPermissionTo')) {
                return $user->withScopeHasPermissionTo($scope, $ability);
            }
        } catch (PermissionDoesNotExist $e) {
            // Skip for undefined permissions
        }
    }
}
