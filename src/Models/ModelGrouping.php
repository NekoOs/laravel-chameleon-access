<?php

namespace NekoOs\ChameleonAccess\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Spatie\Permission\Traits\HasRoles;

/**
 * @package NekoOs\ChameleonAccess\Models
 *
 * @property string $grouping_id
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
