<?php

namespace NekoOs\Laravel\Permission\Concerns;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use NekoOs\Laravel\Permission\Contracts\Scope;
use NekoOs\Laravel\Permission\Models\Grouping;
use NekoOs\Laravel\Permission\Models\ModelGrouping;

/**
 * @package NekoOs\Laravel\Permission
 *
 * @property-read Collection|Grouping[]      $groupings
 * @property-read Collection|ModelGrouping[] $pivot
 *
 * @mixin Model
 */
trait HasScopes
{

    protected $currentScope = false;

    public function withScope(Model $scope): HasScopes
    {
        $this->currentScope = $scope;
        return $this;
    }

    public function withoutScope(): HasScopes
    {
        $this->currentScope = null;
        return $this;
    }

    public function resolveCurrentScope()
    {
        if ($this->currentScope === false) {
            $this->currentScope = app(Scope::class);
        }
        return $this->currentScope;
    }

    public function withScopeGivePermissionTo(Model $scope, ...$permissions): void
    {
        $this->groupScope($scope)
            ->pivot
            ->givePermissionTo(...$permissions);
    }

    public function groupScope(Model $scope): Grouping
    {
        $grouping = Grouping::findByScope($scope);
        $this->groupings()->syncWithoutDetaching($grouping);

        /** @var Grouping $grouping */
        if (!($grouping = $this->getGroupingForScope($scope))) {
            throw (new ModelNotFoundException)->setModel(get_class($scope));
        }

        return $grouping;
    }

    public function groupings(): MorphToMany
    {
        return $this->morphToMany(
            Grouping::class,
            'model',
            ModelGrouping::class,
            )
            ->withPivot('id');
    }

    public function getGroupingForScope(Model $scope): ?Grouping
    {
        return $this->groupings()
            ->where('scope_type', $scope->getMorphClass())
            ->where('scope_id', $scope->id)
            ->first();
    }

    public function withScopeAssignRoles(Model $scope, ...$roles): void
    {
        $this->groupScope($scope)
            ->pivot
            ->assignRole($roles);
    }

    public function withScopeSyncRoles(Model $scope, ...$roles): void
    {
        $this->groupScope($scope)
            ->pivot
            ->syncRoles($roles);
    }
    
    public function withScopeRemoveRole(Model $scope, $role): void
    {
        $this->groupScope($scope)
            ->pivot
            ->removeRole($role);
    }

    public function can($ability, $arguments = [])
    {
        $class = current(Arr::wrap($arguments));

        # Check global permission
        $check = app(Gate::class)->forUser($this)->check($ability, $arguments);

        if (!$check && $scope = $this->resolveCurrentScope()) {
            $check = $this->withScopeHasPermissionTo($scope, $ability);
        }

        return $check;
    }

    public function withScopeHasPermissionTo(Model $scope, $permission): bool
    {
        $grouping = $this->getGroupingForScope($scope);

        return $grouping ? $grouping->pivot->hasPermissionTo($permission) : false;
    }

    public function getRolesForScopeAndAllPossibleParentScopes(Model $scope): Collection
    {
        $rolesViaScope = $this->getRolesForScope($scope);

        if ($directRoles = $this->roles ?? null) {
            $rolesViaScope = $rolesViaScope->merge($directRoles);
        }

        return $rolesViaScope;
    }

    public function getRolesForScope(Model $scope): Collection
    {
        return $this->getGroupingForScope($scope)->pivot->roles ?? collect();
    }

    public function getDirectPermissionsForScopeAndAllPossibleParentScopes(Model $scope): Collection
    {
        $permissions = $this->getDirectPermissionsForScope($scope);

        if (method_exists($this, 'getDirectPermissions')) {
            $permissions = $permissions->merge($this->getDirectPermissions());
        }

        return $permissions;
    }

    public function getDirectPermissionsForScope(Model $scopes): Collection
    {
        return $this->getGroupingForScope($scopes)->pivot->permissions ?? collect();
    }

    public function getPermissionsViaRolesForScopeAndAllPossibleParentScopes(Model $scope): Collection
    {
        $permissions = $this->getPermissionsViaRolesForScope($scope);

        if (method_exists($this, 'getPermissionsViaRoles')) {
            $permissions = $permissions->merge($this->getPermissionsViaRoles());
        }

        return $permissions;
    }

    public function getPermissionsViaRolesForScope(Model $scope): Collection
    {
        return $this->getGroupingForScope($scope)->pivot->getPermissionsViaRoles() ?? collect();
    }

    public function getAllPermissionsForScopeAndAllPossibleParentScopes(Model $scope): Collection
    {
        $permissions = $this->getAllPermissionsForScope($scope);

        if (method_exists($this, 'getAllPermissions')) {
            $permissions = $permissions->merge($this->getAllPermissions());
        }

        return $permissions;
    }

    public function getAllPermissionsForScope(Model $scope): Collection
    {
        return $this->getGroupingForScope($scope)->pivot->getAllPermissions() ?? collect();
    }

}
