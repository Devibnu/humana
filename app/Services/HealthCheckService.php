<?php

namespace App\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthCheckService
{
    public function __construct(
        protected CacheRepository $cache,
        protected QueueManager $queue,
    ) {
    }

    public function checkDatabase(): string
    {
        try {
            DB::connection()->getPdo();

            return 'ok';
        } catch (Throwable $exception) {
            return 'error';
        }
    }

    public function checkQueue(): string
    {
        try {
            $connection = (string) config('queue.default', 'sync');

            if ($connection === 'sync') {
                return 'ok';
            }

            $queueName = (string) config("queue.connections.{$connection}.queue", 'default');
            $this->queue->connection($connection)->size($queueName);

            return 'ok';
        } catch (Throwable $exception) {
            return 'error';
        }
    }

    public function checkCache(): string
    {
        try {
            $cacheKey = 'system_status_health_check';
            $cacheValue = now()->timestamp;

            $this->cache->put($cacheKey, $cacheValue, 60);

            if ((string) $this->cache->get($cacheKey) !== (string) $cacheValue) {
                return 'error';
            }

            $this->cache->forget($cacheKey);

            return 'ok';
        } catch (Throwable $exception) {
            return 'error';
        }
    }

    public function overallStatus(): string
    {
        $override = config('system.health');

        if (in_array($override, ['normal', 'error'], true)) {
            return $override;
        }

        return $this->checkDatabase() === 'ok'
            && $this->checkQueue() === 'ok'
            && $this->checkCache() === 'ok'
            ? 'normal'
            : 'error';
    }
}