<?php

namespace App\Traits;

use App\Relations\HasManyJson as RelationsHasManyJson;

trait HasManyJson
{
    /**
     * Define a custom hasManyJson relationship.
     *
     * @param  string  $related
     * @param  string|null  $foreignKey
     * @param  string|null  $localKey
     * @return \App\Relations\HasManyJson
     */
    public function hasManyJson($related, $foreignKey = null, $localKey = null)
    {
        // If foreign key is not specified, use default
        $foreignKey = $foreignKey ?: $this->getForeignKey();
        
        // Create a new instance of the related model
        $instance = new $related;
        
        // If local key is not specified, use default
        $localKey = $localKey ?: $this->getKeyName();

        // Return a new instance of HasManyJson relationship
        return new RelationsHasManyJson($instance->newQuery(), $this, $foreignKey, $localKey);
    }
}
