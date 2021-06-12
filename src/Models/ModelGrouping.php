<?php

namespace NekoOs\Laravel\Permission\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Support\Collection;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Contracts\Role;
use Spatie\Permission\Traits\HasRoles;

/**
 * @package NekoOs\Laravel\Permission\Models
 *
 * @property string                  $grouping_id
 * @property Collection|Permission[] $permissions
 * @property Collection|Role[]       $roles
 *
 * @mixin Builder
 */
class ModelGrouping extends MorphPivot
{
    use HasRoles;

    protected $guard_name = 'web';

    protected $table = 'model_groupings';

    protected $fillable = [
        'model_id',
        'model_type',
        'grouping_id',
    ];

    public function groupable()
    {
        return $this->morphTo();
    }

}
