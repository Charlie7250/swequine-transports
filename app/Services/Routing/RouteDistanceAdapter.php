<?php

namespace App\Services\Routing;

interface RouteDistanceAdapter
{
    public function resolve(RouteResolutionRequest $request): array;
}
