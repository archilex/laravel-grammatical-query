<?php

namespace GrammaticalQuery\FilterQueryString\Filters\HavingClauses;

trait LessThanEqualClause
{
    private function lessThanEqual($query, $filter, $values)
    {
        foreach((array)$values as $value) {
            $query->having($filter, '<=', $value);
        }
        return $query;
    }
}
