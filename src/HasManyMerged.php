<?php

declare(strict_types=1);

namespace Korridor\LaravelHasManyMerged;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TRelatedModel of Model
 * @extends HasOneOrManyMerged<TRelatedModel>
 */
class HasManyMerged extends HasOneOrManyMerged
{
    /**
     * Create a new has one or many relationship instance.
     *
     * @param  Builder<TRelatedModel>  $query
     * @param  Model  $parent
     * @param  array  $foreignKeys
     * @param  string  $localKey
     * @return void
     */
    public function __construct(Builder $query, Model $parent, array $foreignKeys, string $localKey)
    {
        $this->foreignKeys = $foreignKeys;
        $this->localKey = $localKey;

        parent::__construct($query, $parent);
    }

    /**
     * Get the key value of the parent's local key.
     * Info: From HasOneOrMany class.
     *
     * @return mixed
     */
    public function getParentKey()
    {
        return $this->parent->getAttribute($this->localKey);
    }

    /**
     * Get the fully qualified parent key name.
     *
     * @return string
     */
    public function getQualifiedParentKeyName()
    {
        return $this->parent->qualifyColumn($this->localKey);
    }

    /**
     * Get the name of the "where in" method for eager loading.
     * Note: Similar to whereInMethod of Relation class.
     *
     * @param  Model  $model
     * @param  string  $key
     * @return string
     */
    protected function orWhereInMethod(Model $model, string $key): string
    {
        return $model->getKeyName() === last(explode('.', $key))
        && in_array($model->getKeyType(), ['int', 'integer'])
            ? 'orWhereIntegerInRaw'
            : 'orWhereIn';
    }

    /**
     * Initialize the relation on a set of models.
     *
     * @param  array  $models
     * @param  string  $relation
     * @return array
     */
    public function initRelation(array $models, $relation)
    {
        // Info: From HasMany class
        foreach ($models as $model) {
            $model->setRelation($relation, $this->related->newCollection());
        }

        return $models;
    }

    /**
     * Match the eagerly loaded results to their parents.
     * Info: From HasMany class.
     *
     * @param  array  $models
     * @param  Collection  $results
     * @param  string  $relation
     * @return array
     */
    public function match(array $models, Collection $results, $relation)
    {
        $dictionary = $this->buildDictionary($results);

        // Once we have the dictionary we can simply spin through the parent models to
        // link them up with their children using the keyed dictionary to make the
        // matching very convenient and easy work. Then we'll just return them.
        foreach ($models as $model) {
            if (isset($dictionary[$key = $model->getAttribute($this->localKey)])) {
                $model->setRelation(
                    $relation,
                    $this->related->newCollection($dictionary[$key])->unique($this->related->getKeyName())
                );
            }
        }

        return $models;
    }

    /**
     * Get the results of the relationship.
     *
     * @phpstan-return \Traversable<int, TRelatedModel>
     */
    public function getResults()
    {
        return $this->get();
    }
}
