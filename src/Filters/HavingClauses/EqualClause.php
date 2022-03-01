<?php

namespace GrammaticalQuery\FilterQueryString\Filters\HavingClauses;

trait EqualClause
{
    private function equal($query, $filter, $values)
    {
        foreach((array)$values as $value) {
            $query->having($filter, '=', $value);
        }
        return $query;
    }
}
