<?php

declare(strict_types=1);

/**
 * This file is part of Scout Extended.
 *
 * (c) Algolia Team <contact@algolia.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Algolia\ScoutExtended\Search;

use function in_array;
use Laravel\Scout\Builder;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @internal
 */
final class ModelsResolver
{
    /**
     * @var string
     */
    private static $separator = '_';

    /**
     * Get a set of models from the provided ids.
     *
     * @param \Laravel\Scout\Builder $builder
     * @param  array $models
     * @param  array $ids
     *
     * @return \Illuminate\Support\Collection
     */
    public function from(Builder $builder, array $models, array $ids): Collection
    {
        $models = UuidGenerator::keyByUuid($models);

        $instances = collect();

        foreach ($ids as $id) {
            $model = (new $models[explode(self::$separator, $id)[0]]);
            $modelKey = explode(self::$separator, $id)[1];

            $query = in_array(SoftDeletes::class, class_uses_recursive($model)) ? $model->withTrashed() : $model->newQuery();

            if ($builder->queryCallback) {
                call_user_func($builder->queryCallback, $query);
            }

            $scoutKey = method_exists($model, 'getScoutKeyName') ? $model->getScoutKeyName() : $model->getQualifiedKeyName();

            if ($instance = $query->where($scoutKey, $modelKey)->get()->first()) {
                $instances->push($instance);
            }
        }

        return $instances;
    }
}
