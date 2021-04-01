<?php

namespace NekoOs\ChameleonAccess\Concerns;

use Illuminate\Contracts\Auth\Access\Gate;
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

    public function getScopedPermissions(): Collection
    {
        /** @noinspection PhpUndefinedFieldInspection */
        return $this->groupings->map->pivot->flatMap->permissions;
    }

    public function getScopedPermissionsViaRoles(): Collection
    {
        /** @noinspection PhpUndefinedFieldInspection */
        return $this->groupings->map->pivot->flatMap->getPermissionsViaRoles();
    }

    public function getAllScopedPermissions(): Collection
    {
        /** @noinspection PhpUndefinedFieldInspection */
        return $this->groupings->map->pivot->flatMap->getAllPermissions();
    }

    public function getScopedRoles(): Collection
    {
        /** @noinspection PhpUndefinedFieldInspection */
        return $this->groupings->map->pivot->flatMap->roles;
    }

    public function can($ability, $arguments = [])
    {
        $scope = current($arguments);

        $check = app(Gate::class)->forUser($this)->check($ability, $arguments);

        if (!$check && $scope instanceof Model) {
            $check = $this->groupings->first(static function (Grouping $grouping) use ($ability) {
                return $grouping->pivot->hasPermissionTo($ability);
            });
        }

        return $check;
    }
}
