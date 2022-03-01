<?php

namespace GrammaticalQuery\FilterQueryString\Filters;

use GrammaticalQuery\FilterQueryString\Filters\HavingClauses\{
    EqualClause,
    GreaterThanClause,
    GreaterThanEqualClause,
    LessThanClause,
    LessThanEqualClause,
    NotEqualClause,
    WhereBetweenClause,
};

use Illuminate\Database\Eloquent\Builder;

class RelationshipSumClause extends BaseClause {
    use EqualClause;
    use NotEqualClause;
    use GreaterThanClause;
    use GreaterThanEqualClause;
    use LessThanClause;
    use LessThanEqualClause;
    use WhereBetweenClause;

    protected $availableFilters = [
        'default' => 'eq',
        'eq' => 'equal',
        'gt' => 'greaterThan',
        'gtEq' => 'greaterThanEqual',
        'lt' => 'lessThan',
        'ltEq' => 'lessThanEqual',
        'notEq' => 'notEqual',
        'between' => 'between',
    ];

    protected function apply($query): Builder
    {
        if (is_array($this->values)) {
            foreach ((array) $this->values as $relationship => $filters) {
                
                if (! $this->relationshipExists($query, $relationship)) {
                    continue;
                }
                foreach ((array) $filters as $filter => $values) {
                    
                    // Create a safe alias to avoid duplicate column conflicts when loading the sum
                    // in the filterless view
                    $alias = \Illuminate\Support\Str::snake(
                        preg_replace('/[^[:alnum:][:space:]_]/u', '', "$relationship " . "sum" . " $filter" . "_safe")
                    );

                    $query->withSum($relationship . ' as ' . $alias, $filter);

                    if (is_array($values)) {
                        $this->resolver($query, $alias, $values);                      
                    } else  {
                        $query->having($alias, '=', $values);
                    }
                }
            }
        }

        return $query;
    }

    protected function relationshipExists($query, $relationship)
    {
        $relationships = explode('.', $relationship);
        $model = $query->getModel();

        foreach ($relationships as $relationship) {
            if (! method_exists($model, $relationship)) {
                return false;
            }

            $model = $model->{$relationship}()->getModel();
        }

        return true;
    }

    protected function validate($value): bool {
        return !is_null($value);
    }

    private function resolver($query, $filter, $values)
    {
        $method = $this->availableFilters['default'];
        if(is_array($values) && $this->isAssoc($values)) {
            foreach((array)$values as $key => $value) {
                $method = $this->availableFilters[$key] ?? $this->availableFilters['default'];
                $query = $this->{$method}($query, $filter, $value);
            }
        } else {
            $query = $this->{$method}($query, $filter, $values);
        }

        return $query;
    }

    private function isAssoc(array $array) {
        return count(array_filter(array_keys($array), 'is_string')) > 0;
    }

    private function orWhere($query, $filter, $values)
    {
        $query->where(function($query) use($values, $filter) {
            foreach((array)$values as $value) {
                $query->orWhere($filter, $value);
            }
        });

        return $query;
    }

    private function where($query, $filter, $values)
    {
        foreach((array)$values as $value) {
            $query->where($filter, $value);
        }
        return $query;
    }
}
