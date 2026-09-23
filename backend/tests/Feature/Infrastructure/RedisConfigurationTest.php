<?php

namespace Tests\Feature\Infrastructure;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;
use Exception;

class RedisConfigurationTest extends TestCase
{
    public function test_laravel_can_connect_to_redis()
    {
        try {
            $redis = Redis::connection();
            $ping = $redis->ping();
            
            // phpredis returns true or '+PONG' for ping depending on version/config
            $this->assertTrue($ping === true || $ping === '+PONG' || $ping === 'PONG');
        } catch (Exception $e) {
            $this->fail("Could not connect to Redis: " . $e->getMessage());
        }
    }

    public function test_cache_can_write_and_read_through_redis()
    {
        $store = Cache::store('redis');
        $key = 'test_redis_cache_key_' . uniqid();
        $value = 'test_redis_cache_value_' . uniqid();

        $store->put($key, $value, 10);

        $this->assertEquals($value, $store->get($key));

        $store->forget($key);
        $this->assertNull($store->get($key));
    }

    public function test_queue_configuration_exists_for_redis()
    {
        $config = config('queue.connections.redis');
        
        $this->assertNotNull($config);
        $this->assertEquals('redis', $config['driver']);
    }
}
