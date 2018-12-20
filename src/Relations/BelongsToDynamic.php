<?php namespace Markok\Eloquent\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @property \Illuminate\Database\Eloquent\Builder $query
 */
class BelongsToDynamic extends BelongsTo
{
    protected $subQuery;
    protected $subQueryOwnerKey;
    protected $subQueryForeignKey;

    public function __construct(Builder $query, Builder $subQuery, $subQueryForeignKey, $subQueryOwnerKey, Model $child, $foreignKey, $ownerKey, $relation)
    {
        $this->subQuery = $subQuery;
        $this->subQueryOwnerKey = $subQueryOwnerKey;
        $this->subQueryForeignKey = $subQueryForeignKey;

        parent::__construct($query, $child, $foreignKey, $ownerKey, $relation);
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        if (static::$constraints) {
            if (!$this->child->{$this->foreignKey}) {
                $subQuery = clone $this->subQuery;
                // Execute the subQuery with a condition to find the dynamic value with a
                // foreign key matching the primary key value of the child model
                $result = $subQuery
                    ->select($this->subQueryForeignKey)
                    ->where($this->subQueryOwnerKey, $this->child->getKey())
                    ->first();
                if ($result) {
                    $value = $result->getAttribute($this->subQueryForeignKey);
                    $this->child->setAttribute($this->foreignKey, $value);
                }
            }
            parent::addConstraints();
        }
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param  array  $models
     * @return void
     */
    public function addEagerConstraints(array $models)
    {
        // Get an array of 'key' values for the attribute of the child model that is used in the dynamic relationship
        $keyValues = $this->getEagerModelKeys($models);

        $this->query = clone $this->subQuery;

        $this->query->whereIn($this->subQueryOwnerKey, $keyValues);
    }

    /**
     * Gather the keys from an array of related models.
     *
     * @param  array  $models
     * @return array
     */
    protected function getEagerModelKeys(array $models)
    {
        $keys = [];

        // First we need to gather all of the keys from the parent models so we know what
        // to query for via the eager loading query. We will add them to an array then
        // execute a "where in" statement to gather up all of those related records.
        foreach ($models as $model) {
            if (! is_null($value = $model->{$this->subQueryForeignKey})) {
                $keys[] = $value;
            }
        }

        // If there are no keys that were not null we will just return an array with null
        // so this query wont fail plus returns zero results, which should be what the
        // developer expects to happen in this situation. Otherwise we'll sort them.
        if (count($keys) === 0) {
            return [null];
        }

        sort($keys);

        return array_values(array_unique($keys));
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param  array   $models
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @param  string  $relation
     * @return array
     */
    public function match(array $models, Collection $results, $relation)
    {
        // Use the newly provided 'subQueryForeignKey' and 'subQueryOwnerKey' attribute names to
        // match up eager loaded models with their counterparts

        $foreign = $this->subQueryForeignKey;
        $owner = $this->subQueryOwnerKey;

        // First we will get to build a dictionary of the child models by their primary
        // key of the relationship, then we can easily match the children back onto
        // the parents using that dictionary and the primary key of the children.
        $dictionary = [];

        foreach ($results as $result) {
            // Only allow one model match for each 'owner' value
            if (isset($dictionary[$result->getAttribute($owner)])) {
                continue;
            }
            $dictionary[$result->getAttribute($owner)] = $result;
        }

        // Once we have the dictionary constructed, we can loop through all the parents
        // and match back onto their children using these keys of the dictionary and
        // the primary key of the children to map them onto the correct instances.
        foreach ($models as $model) {
            if (isset($dictionary[$model->{$foreign}])) {
                $model->setRelation($relation, $dictionary[$model->{$foreign}]);
            }
        }

        return $models;
    }
}
