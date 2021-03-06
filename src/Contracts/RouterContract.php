<?php

namespace PHPageBuilder\Contracts;

interface RouterContract
{
    /**
     * Return the page corresponding to the given route.
     *
     * @param $route
     * @return PageContract|null
     */
    public function resolve($route);
}
