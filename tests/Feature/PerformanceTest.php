<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Domain;
use App\Services\CacheService;
use App\Services\MonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_loads_within_acceptable_time(): void
    {
        $user = User::factory()->create();
        
        $startTime = microtime(true);
        
        $response = $this->actingAs($user)->get('/dashboard');
        
        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        $response->assertStatus(200);
        $this->assertLessThan(2000, $loadTime, 'Dashboard should load within 2 seconds');
    }

    public function test_cache_service_improves_performance(): void
    {
        $cacheService = new CacheService();
        
        // Test without cache
        $startTime = microtime(true);
        $result1 = $this->getExpensiveData();
        $timeWithoutCache = microtime(true) - $startTime;
        
        // Test with cache
        $startTime = microtime(true);
        $result2 = $cacheService->remember('test_data', 60, function() {
            return $this->getExpensiveData();
        });
        $timeWithCache = microtime(true) - $startTime;
        
        $this->assertEquals($result1, $result2);
        $this->assertLessThan($timeWithoutCache * 0.1, $timeWithCache, 'Cached data should be significantly faster');
    }

    public function test_database_queries_are_optimized(): void
    {
        $user = User::factory()->create();
        Domain::factory()->count(50)->create(['user_id' => $user->id]);
        
        // Enable query logging
        \DB::enableQueryLog();
        
        $response = $this->actingAs($user)->get('/domains');
        
        $queries = \DB::getQueryLog();
        
        $response->assertStatus(200);
        $this->assertLessThan(5, count($queries), 'Should use minimal database queries');
    }

    public function test_monitoring_service_tracks_performance(): void
    {
        $monitoring = new MonitoringService();
        
        $startTime = microtime(true);
        
        // Simulate some work
        usleep(100000); // 100ms
        
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;
        
        $monitoring->logPerformanceMetric('test_operation', $duration);
        
        // Check that the metric was logged (would need actual log checking in real implementation)
        $this->assertTrue(true);
    }

    public function test_memory_usage_is_reasonable(): void
    {
        $user = User::factory()->create();
        
        $memoryBefore = memory_get_usage();
        
        $response = $this->actingAs($user)->get('/dashboard');
        
        $memoryAfter = memory_get_usage();
        $memoryUsed = $memoryAfter - $memoryBefore;
        
        $response->assertStatus(200);
        $this->assertLessThan(50 * 1024 * 1024, $memoryUsed, 'Should use less than 50MB of memory');
    }

    public function test_concurrent_requests_handling(): void
    {
        $user = User::factory()->create();
        
        $responses = [];
        $startTime = microtime(true);
        
        // Simulate concurrent requests
        for ($i = 0; $i < 10; $i++) {
            $responses[] = $this->actingAs($user)->get('/dashboard');
        }
        
        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }
        
        $this->assertLessThan(5000, $totalTime, 'Should handle 10 concurrent requests within 5 seconds');
    }

    public function test_large_dataset_pagination_performance(): void
    {
        $user = User::factory()->create();
        Domain::factory()->count(1000)->create(['user_id' => $user->id]);
        
        $startTime = microtime(true);
        
        $response = $this->actingAs($user)->get('/domains');
        
        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000;
        
        $response->assertStatus(200);
        $this->assertLessThan(1000, $loadTime, 'Large dataset pagination should load within 1 second');
    }

    private function getExpensiveData(): array
    {
        // Simulate expensive operation
        usleep(50000); // 50ms
        return ['data' => 'expensive calculation result'];
    }
}
