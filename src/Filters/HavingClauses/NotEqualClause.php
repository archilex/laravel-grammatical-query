<?php

namespace GrammaticalQuery\FilterQueryString\Filters\HavingClauses;

trait NotEqualClause
{
    private function notEqual($query, $filter, $values)
    {
        foreach((array)$values as $value) {
            $query->having($filter, '!=', $value);
        }
        return $query;
    }
}
