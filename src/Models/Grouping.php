<?php

namespace NekoOs\ChameleonAccess\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\PostgresBuilder;
use Spatie\Permission\Models\Role;

/**
 * @package App\Models
 *
 * @property ModelGrouping pivot
 *
 * @mixin Builder
 */
class Grouping extends Model
{
    public $incrementing = false;
    public $timestamps = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id',
        'scope_type',
        'scope_id',
    ];

    /**
     * @param Model $scope
     *
     * @return static|null
     */
    public static function findByScope(Model $scope): ?self
    {
        [$type, $id] = [$scope->getMorphClass(), $scope->getAttribute('id')];

        return (new static())
            ->where('scope_type', $type)
            ->where('scope_id', $id)
            ->first();
    }

    public function models()
    {
        return $this->morphedByMany(
            Role::class,
            'model',
            ModelGrouping::class,
            );
    }

    /**
     * @param Model|array $scope
     *
     * @return Grouping
     */
    public function appendScope(Model $scope): self
    {
        $id = $this->getAttribute('id');
        if ($id && !$this->exists && !$this->find($id)) {
            throw new ModelNotFoundException("Scope '$id' not is a grouping record");
        }

        if (!($child = $this->lastChild())) {
            $current = implode('.', array_filter([$id, 0], 'strlen'));
        } else {
            $current = $child->getAttribute('id');
        }

        $scope = [
            'scope_type' => $scope->getMorphClass(),
            'scope_id'   => $scope->getAttribute('id'),
        ];

        $scope['id'] = self::generateSequence($current);

        return $this->create($scope);
    }

    public function lastChild()
    {
        $pieces = explode('.', $this->getAttribute('id'));
        $regexp = '^' . implode('\.', [...$pieces, '\d+']) . '$';

        $operator = 'regexp';
        $type = "UNSIGNED";

        if ($this->getConnection()->getSchemaBuilder() instanceof PostgresBuilder) {
            $operator = '~';
            $type = 'INTEGER';
        }

        return (new static)
            ->where('id', $operator, $regexp)
            ->orderByRaw("CAST(REPLACE(id, '.', '0') AS {$type}) DESC")
            ->first();
    }

    /**
     * @param string|null $seed
     *
     * @return string
     */
    public static function generateSequence(?string $seed): string
    {
        $pieces = explode('.', $seed);
        array_pop($pieces);
        $regexp = '^' . implode('\.', [...$pieces, '\d+']) . '$';

        $operator = 'regexp';
        $type = "UNSIGNED";

        if ((new static())->getConnection()->getSchemaBuilder() instanceof PostgresBuilder) {
            $operator = '~';
            $type = 'INTEGER';
        }

        $scope = (new static)
            ->where('id', $operator, $regexp)
            ->orderByRaw("CAST(REPLACE(id, '.', '0') AS $type) DESC")
            ->first();

        $pieces = explode('.', optional($scope)->getAttribute('id') ?? $seed);
        $gen = array_pop($pieces) + 1;
        return implode('.', [...$pieces, $gen]);
    }
}
