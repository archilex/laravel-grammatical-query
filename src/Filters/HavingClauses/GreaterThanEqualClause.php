<?php

namespace GrammaticalQuery\FilterQueryString\Filters\HavingClauses;

trait GreaterThanEqualClause
{
    private function greaterThanEqual($query, $filter, $values)
    {
        foreach((array)$values as $value) {
            $query->having($filter, '>=', $value);
        }
        return $query;
    }
}
