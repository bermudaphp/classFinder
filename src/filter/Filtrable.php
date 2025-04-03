<?php

namespace Bermuda\ClassFinder\Filter;

use Bermuda\ClassFinder\ClassFinderInterface;

interface Filtrable
{
    public function withFilter(FilterInterface $filter): Filtrable&ClassFinderInterface;
    public function withFilters(iterable $filters): Filtrable&ClassFinderInterface;
}