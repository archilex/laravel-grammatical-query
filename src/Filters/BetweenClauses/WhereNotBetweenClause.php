<?php

namespace GrammaticalQuery\FilterQueryString\Filters\BetweenClauses;

trait WhereNotBetweenClause
{
    private function notBetween($query, $filter, $values)
    {
        $query->whereNotBetween($filter, (array) $values);
        return $query;
    }
}