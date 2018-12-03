<?php namespace Markok\Eloquent\Traits;

use Markok\Eloquent\Relations\BelongsToDynamic as Relation;
use Illuminate\Support\Str;

trait BelongsToDynamic
{

    /**
     * Define an inverse one-to-one or many relationship using a dynamic foreign key value from a subQuery
     *
     * @param  string $related
     * @param  \Illuminate\Database\Eloquent\Builder $subQuery
     * @param  string $subQueryForeignKey
     * @param  string $subQueryOwnerKey
     * @param  string $foreignKey
     * @param  string $ownerKey
     * @param  string $relation
     * @return \Markok\Eloquent\Relations\BelongsToDynamic
     */
    protected function belongsToDynamic($related, $subQuery, $subQueryForeignKey, $subQueryOwnerKey, $foreignKey = null, $ownerKey = null, $relation = null)
    {
        // If no relation name was given, we will use this debug backtrace to extract
        // the calling method's name and use that as the relationship name as most
        // of the time this will be what we desire to use for the relationships.
        if (is_null($relation)) {
            $relation = $this->guessBelongsToRelation();
        }

        $instance = $this->newRelatedInstance($related);

        // If no foreign key was supplied, we can use a backtrace to guess the proper
        // foreign key name by using the name of the relationship function, which
        // when combined with an "_id" should conventionally match the columns.
        if (is_null($foreignKey)) {
            $foreignKey = Str::snake($relation).'_'.$instance->getKeyName();
        }

        // Once we have the foreign key names, we'll just create a new Eloquent query
        // for the related models and returns the relationship instance which will
        // actually be responsible for retrieving and hydrating every relations.
        $ownerKey = $ownerKey ?: $instance->getKeyName();

        return new Relation(
            $instance->newQuery(), $subQuery, $subQueryForeignKey, $subQueryOwnerKey, $this, $foreignKey, $ownerKey, $relation
        );
    }
}