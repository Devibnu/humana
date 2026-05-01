<?php

namespace App\Support;

use App\Services\HealthCheckService;

class SystemStatusChecker
{
    public function __construct(protected HealthCheckService $healthCheckService)
    {
    }

    public function status(): string
    {
        return $this->healthCheckService->overallStatus();
    }
}