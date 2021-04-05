<?php

namespace NekoOs\ChameleonAccess\Concerns;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use NekoOs\ChameleonAccess\Models\Grouping;
use NekoOs\ChameleonAccess\Models\ModelGrouping;
use Spatie\Permission\Traits\HasRoles;

/**
 * @package NekoOs\ChameleonAccess
 *
 * @property-read Collection|Grouping[]      $groupings
 * @property-read Collection|ModelGrouping[] $pivot
 *
 * @mixin Model
 */
trait HasScopes
{

    public function withScopeGivePermissionTo(Model $scope, ...$permissions): void
    {
        $this->groupScope($scope)
            ->pivot
            ->givePermissionTo(...$permissions);
    }


    public function withScopeHasPermissionTo(Model $scope, $permission): bool
    {
        $grouping = $this->getGroupingForScope($scope);

        return $grouping ? $grouping->pivot->hasPermissionTo($permission) : false;
    }

    public function getGroupingForScope(Model $scope): ?Grouping
    {
        return $this->groupings()
            ->where('scope_type', $scope->getMorphClass())
            ->where('scope_id', $scope->id)
            ->first();
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

    public function getDirectPermissionsForScope(Model $scopes): Collection
    {
        return $this->getGroupingForScope($scopes)->pivot->permissions ?? collect();
    }

    public function getPermissionsViaRolesForScope(Model $scope): Collection
    {
        return $this->getGroupingForScope($scope)->pivot->getPermissionsViaRoles() ?? collect();
    }

    public function getAllPermissionsForScope(Model $scope): Collection
    {
        return $this->getGroupingForScope($scope)->pivot->getAllPermissions() ?? collect();
    }

    public function getRolesForScope(Model $scope): Collection
    {
        return $this->getGroupingForScope($scope)->pivot->roles ?? collect();
    }

    public function can($ability, $arguments = [])
    {
        $scope = current(Arr::wrap($arguments));

        $check = app(Gate::class)->forUser($this)->check($ability, $arguments);

        if (!$check && $scope instanceof Model) {
            $check = $this->withScopeHasPermissionTo($scope, $ability);
        }

        return $check;
    }

    public function getRolesForScopeAndAllPossibleParentScopes(Model $scope): Collection
    {
        $rolesViaScope = $this->getRolesForScope($scope);

        if ($directRoles = $this->roles ?? null) {
            $rolesViaScope = $rolesViaScope->merge($directRoles);
        }

        return $rolesViaScope;
    }



    public function getDirectPermissionsForScopeAndAllPossibleParentScopes(Model $scope): Collection
    {
        $permissions = $this->getDirectPermissionsForScope($scope);

        if (method_exists($this, 'getDirectPermissions')) {
            $permissions = $permissions->merge($this->getDirectPermissions());
        }

        return $permissions;
    }

    public function getPermissionsViaRolesForScopeAndAllPossibleParentScopes(Model $scope): Collection
    {
        $permissions = $this->getPermissionsViaRolesForScope($scope);

        if (method_exists($this, 'getPermissionsViaRoles')) {
            $permissions = $permissions->merge($this->getPermissionsViaRoles());
        }

        return $permissions;
    }

    public function getAllPermissionsForScopeAndAllPossibleParentScopes(Model $scope): Collection
    {
        $permissions =  $this->getAllPermissionsForScope($scope);

        if (method_exists($this, 'getAllPermissions')) {
            $permissions = $permissions->merge($this->getAllPermissions());
        }

        return $permissions;
    }

}
