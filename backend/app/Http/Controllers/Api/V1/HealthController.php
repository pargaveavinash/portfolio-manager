<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'status' => 'ok',
            ],
        ]);
    }

    public function ready(): JsonResponse
    {
        $database = $this->checkDatabase();
        $redis = $this->checkRedis();

        $healthy = $database && $redis;

        return response()->json([
            'data' => [
                'status' => $healthy ? 'ok' : 'error',
                'checks' => [
                    'database' => $database ? 'ok' : 'error',
                    'redis' => $redis ? 'ok' : 'error',
                ],
            ],
        ], $healthy ? 200 : 503);
    }

    private function checkDatabase(): bool
    {
        try {
            DB::select('SELECT 1');

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function checkRedis(): bool
    {
        try {
            Redis::ping();

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}