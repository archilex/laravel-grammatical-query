<?php

namespace GrammaticalQuery\FilterQueryString\Filters\HavingClauses;

trait GreaterThanClause
{
    private function greaterThan($query, $filter, $values)
    {
        foreach((array)$values as $value) {
            $query->having($filter, '>', $value);
        }
        return $query;
    }
}
