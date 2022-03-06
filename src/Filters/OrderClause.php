<?php

namespace GrammaticalQuery\FilterQueryString\Filters;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
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

        $relationshipArray = $this->values['relationship'][$relationship];
        $relationshipField = key($relationshipArray);
        $direction = $relationshipArray[$relationshipField];
        
        if (count($relationships) === 1) {
            
            // Belongs To
            if ($this->getMethodType($model, $relationship) === 'BelongsTo') {
                return $query->orderBy($relationshipModel::select($relationshipTable . '.' . $relationshipField)
                    ->whereColumn($relationshipTable . '.' . $relationshipPrimaryKey, $table . '.' . $relationshipForeignKey), $direction);
            }

            // Has One (of Many with Where Clause)
            // Allows a Has One relationship to be defined on a HasMany relationship through a "where" clause:
            // $this->hasOne(OrderItem::class)->where('sort_order', 1). The where clause is to be passed as
            // a raw query "sort_order = 1". The table name will automatically be prepended. 
            return $query->select($table . '.*', DB::raw('MAX(' . $relationshipTable . '. ' . $relationshipField . ') as '. $relationshipField . ''))
                ->join($relationshipTable, $relationshipTable . '.' . $model->getForeignKey(), '=', $table . '.' . $model->getKeyName())
                ->groupBy($table . '.id')
                ->when(isset($relationshipArray['whereRaw']) && $relationshipArray['whereRaw'], function ($query) use ($relationshipTable, $relationshipArray) {
                    $query->whereRaw($relationshipTable . '.' . $relationshipArray['whereRaw']);
                })
                ->orderBy($relationshipField, $direction);
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
                return false;
            }

            $model = $model->{$relationship}()->getModel();
        }

        return true;
    }

    protected function relationshipTypeIsValid($class, $method): bool
    {
        return $this->getMethodType($class, $method) === 'BelongsTo' || $this->getMethodType($class, $method) === 'HasOne'; 
    }

    protected function getMethodType($class, $method): string
    {
        $obj = new $class;
        $class = get_class($obj->{$method}());
        $type = (new \ReflectionClass($class))->getShortName();

        return $type;
    }
}
