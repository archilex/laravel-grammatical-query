<?php

namespace GrammaticalQuery\FilterQueryString\Filters\HavingClauses;

trait LessThanClause
{
    private function lessThan($query, $filter, $values)
    {
        foreach((array)$values as $value) {
            $query->having($filter, '<', $value);
        }
        return $query;
    }
}