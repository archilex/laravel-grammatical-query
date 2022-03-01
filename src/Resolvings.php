<?php

namespace GrammaticalQuery\FilterQueryString;

trait Resolvings {

    private function resolve($filterName, $values)
    {
        if($this->isCustomFilter($filterName)) {
            return $this->resolveCustomFilter($filterName, $values);
        }

        $availableFilter = $this->availableFilters[$filterName] ?? $this->availableFilters['default'];

        $values = $this->applyTransformers($filterName, $values);

        return app($availableFilter, ['filter' => $filterName, 'values' => $values]);
    }

    private function applyTransformers($filterName, $values)
    {
        $values = $this->convertToCents($filterName, $values);

        return $values;
    }

    private function convertToCents($filterName, $values)
    {
        if (empty($this->centsFields)) {
            return $values;
        }

        if (
            (in_array($filterName, $this->centsFields)) || 
            ($filterName === 'relationship' && in_array(key($values) . '.' . key($values[key($values)]), $this->centsFields)) ||
            ($filterName === 'relationshipSum' && in_array(key($values) . '.' . key($values[key($values)]), $this->centsFields))
        ) {  
            array_walk_recursive($values, function (&$value) {
                if (! is_array($value) && is_numeric($value)) {
                    $value = $value * 100;
                }
            });
        }

        return $values;
    }

    private function resolveCustomFilter($filterName, $values)
    {
        return $this->getClosure($this->makeCallable($filterName), $values);
    }

    private function makeCallable($filter)
    {
        return static::class.'@'.$filter;
    }

    private function isCustomFilter($filterName)
    {
        return method_exists($this, $filterName);
    }

    private function getClosure($callable, $values)
    {
        return function ($query, $nextFilter) use ($callable, $values) {
            return app()->call($callable, ['query' => $nextFilter($query), 'value' => $values]);
        };
    }
}
