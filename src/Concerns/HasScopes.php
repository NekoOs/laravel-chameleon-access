<?php

namespace NekoOs\ChameleonAccess\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;
use NekoOs\ChameleonAccess\Models\Grouping;
use NekoOs\ChameleonAccess\Models\ModelGrouping;

/**
 * @package NekoOs\ChameleonAccess
 *
 * @property-read Collection|Grouping[]      $groupings
 * @property-read Collection|ModelGrouping[] $pivot
 */
trait HasScopes
{

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
        $grouping = $this->groupings()
            ->where('scope_id', $scope->getAttribute('id'))
            ->where('scope_type', $scope->getMorphClass())
            ->firstOrFail();

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

    public function getAllScopedPermissions(): Collection
    {
        $permissions = [];

        /** @noinspection PhpUndefinedFieldInspection */
        $this->groupings->map->pivot->map(function (ModelGrouping $modelGrouping) use (&$permissions) {
            $modelGrouping
                ->getAllPermissions()
                ->each(function ($permission) use (&$permissions, $modelGrouping) {
                    $permissions[$permission->name] = ($permissions[$permission->name] ?? []) + [$modelGrouping->grouping_id];
                });
        });

        return collect($permissions);
    }

    public function getScopedPermissionsViaRoles(): Collection
    {
        $permissions = [];

        /** @noinspection PhpUndefinedFieldInspection */
        $this->groupings->map->pivot->map(function (ModelGrouping $modelGrouping) use (&$permissions) {
            $modelGrouping
                ->getPermissionsViaRoles()
                ->each(function ($permission) use (&$permissions, $modelGrouping) {
                    $permissions[$permission->name] = ($permissions[$permission->name] ?? []) + [$modelGrouping->grouping_id];
                });
        });

        return collect($permissions);
    }


    public function getAllScopedRoles(): Collection
    {
        /** @noinspection PhpUndefinedFieldInspection */
        return $this->groupings->map->pivot->flatMap->roles;
    }
}
