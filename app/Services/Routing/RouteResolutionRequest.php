<?php

namespace App\Services\Routing;

use Carbon\CarbonInterface;

class RouteResolutionRequest
{
    public const ROUTE_PROFILE = 'car';

    public function __construct(
        public readonly int $resolutionId,
        public readonly string $depotPostcode,
        public readonly string $pickupPostcode,
        public readonly string $dropoffPostcode,
        public readonly CarbonInterface $requestStartedAt,
        public readonly array $requestContext,
    ) {}
}
