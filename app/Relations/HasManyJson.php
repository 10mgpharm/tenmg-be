<?php

namespace App\Relations;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HasManyJson extends HasMany
{
    /**
     * Create a new HasManyJson instance.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $foreignKey
     * @param  string  $localKey
     * @return void
     */
    public function __construct(Builder $query, Model $parent, $foreignKey, $localKey)
    {
        parent::__construct($query, $parent, $foreignKey, $localKey);
    }


    /**
     * Add eager load constraints to the query.
     *
     * @param  array  $models
     * @return void
     */
    public function addEagerConstraints(array $models)
    {
        $this->query->whereIn($this->foreignKey, $this->getKeys($models, $this->localKey));
    }


    /**
     * Get the unique keys from an array of models.
     *
     * @param  array  $models
     * @param  string|null  $key
     * @return array
     */
    protected function getKeys(array $models, $key = null)
    {

        $keys = [];
        collect($models)->each(function ($value) use ($key, &$keys) {
            $keys = array_merge($keys, (array)$value->getAttribute($key));
        });
        return array_unique($keys);
    }


    /**
     * Match eagerly loaded models to their parents.
     *
     * @param  array  $models
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @param  string  $relation
     * @return array
     */
    public function matchMany(array $models, Collection $results, $relation)
    {

        $foreign = $this->getForeignKeyName();

        $dictionary = $results->mapToDictionary(function ($result) use ($foreign) {
            return [$result->{$foreign} => $result];
        })->all();

        foreach ($models as $model) {
            $ids = (array) $model->getAttribute($this->localKey);
            $collection = collect();
            foreach ($ids as $id) {
                if (isset($dictionary[$id]))
                    $collection = $collection->merge($this->getRelationValue($dictionary, $id, 'many'));
            }
            $model->setRelation($relation, $collection);
        }

        return $models;
    }
}
