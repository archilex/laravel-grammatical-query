<?php

namespace GrammaticalQuery\FilterQueryString\Filters;

use Illuminate\Database\Eloquent\Builder;

class OrderClause extends BaseClause {

    protected function apply($query): Builder
    {        
        if(is_array($this->values)) {
            if ($this->isRelationship()) {
                return $this->orderByRelationship($query);
            }
            
            foreach ((array) $this->values as $field => $order) {
                $order = $order == 'asc'? 'asc': 'desc';
                $query->orderBy($field, $order);
            }
        } else {
            $query->orderBy($this->values, 'asc');
        }

        return $query;
    }

    protected function validate($value): bool 
    {
        return !is_null($value);
    }

    protected function isRelationship(): bool
    {
        return array_key_exists('relationship', $this->values);
    }

    protected function orderByRelationship($query): Builder
    {
        if (! $this->relationshipQueryIsValid($query)) {
            return $query;
        }

        $model = $query->getModel();                
        $table = $model->getTable();
        $relationship = key($this->values['relationship']);
        $relationships = explode('.', $relationship);

        $relationshipModel = $model->{$relationships[0]}()->getRelated();
        $relationshipTable = $relationshipModel->getTable();
        $relationshipPrimaryKey = $relationshipModel->getKeyName();
        $relationshipForeignKey = $relationshipModel->getForeignKey();

        $relationshipField = key($this->values['relationship'][$relationship]);
        $direction = $this->values['relationship'][$relationship][$relationshipField];
        
        if (count($relationships) === 1) {
            return $query->orderBy($relationshipModel::select($relationshipTable . '.' . $relationshipField)
                ->whereColumn($relationshipTable . '.' . $relationshipPrimaryKey, $table . '.' . $relationshipForeignKey), $direction);
        }

        if (count($relationships) === 2) {
            $relationshipModel2 = $relationshipModel->{$relationships[1]}()->getRelated();
            $relationshipTable2 = $relationshipModel->{$relationships[1]}()->getRelated()->getTable();
            $relationshipPrimaryKey2 = $relationshipModel->{$relationships[1]}()->getRelated()->getKeyName();
            $relationshipForeignKey2 = $relationshipModel->{$relationships[1]}()->getRelated()->getForeignKey();

            return $query->orderBy($relationshipModel2::select($relationshipTable2 . '.' . $relationshipField)
                ->join($relationshipTable, $relationshipTable . '.' . $relationshipForeignKey2, '=', $relationshipTable2 . '.' . $relationshipPrimaryKey2)
                ->whereColumn($relationshipTable . '.' . $relationshipPrimaryKey, $table . '.' . $relationshipForeignKey), $direction);
        }

        return $query;
    }

    protected function relationshipQueryIsValid($query): bool
    {
        if (! is_array($this->values['relationship'])) {
            return $query;
        }

        $relationship = key($this->values['relationship']);

        if (! is_array($this->values['relationship'][$relationship])) {
            return $query;
        }
        
        $relationships = explode('.', $relationship);
        $model = $query->getModel();

        foreach ($relationships as $relationship) {
            if (! method_exists($model, $relationship)) {
                return false;
            }

            if (! $this->relationshipTypeIsValid($model, $relationship)) {
                return $query;
            }

            $model = $model->{$relationship}()->getModel();
        }

        return true;
    }

    protected function relationshipTypeIsValid($class, $method): bool
    {
        return $this->getMethodType($class, $method) === 'BelongsTo'; 
    }

    protected function getMethodType($class, $method): string
    {
        $obj = new $class;
        $class = get_class($obj->{$method}());
        $type = (new \ReflectionClass($class))->getShortName();

        return $type;
    }
}
