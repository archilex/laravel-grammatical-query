<?php

namespace GrammaticalQuery\FilterQueryString\Filters\HavingClauses;

trait WhereBetweenClause
{
    private function between($query, $filter, $values)
    {
        if (count($values) === 2) {
            $query->having($filter, '>=', $values[0])->having($filter, '<=', $values[1]);
        }
        return $query;
    }
}