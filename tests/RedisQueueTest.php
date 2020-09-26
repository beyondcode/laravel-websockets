<?php

namespace BeyondCode\LaravelWebSockets\Test;

use BeyondCode\LaravelWebSockets\Queue\AsyncRedisQueue;
use Illuminate\Container\Container;
use Illuminate\Contracts\Redis\Factory;
use Illuminate\Queue\Jobs\RedisJob;
use Illuminate\Queue\Queue;
use Illuminate\Support\Carbon;
use Illuminate\Support\InteractsWithTime;
use Illuminate\Support\Str;
use Mockery as m;
use stdClass;

class RedisQueueTest extends TestCase
{
    use InteractsWithTime;

    /**
     * The testing queue for Redis.
     *
     * @var \Illuminate\Queue\RedisQueue
     */
    private $queue;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->runOnlyOnRedisReplication();

        $this->queue = new AsyncRedisQueue(
            $this->app['redis'], 'default', null, 60, null
        );

        $this->queue->setContainer($this->app);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }

    public function test_expired_jobs_are_popped()
    {
        $jobs = [
            new RedisQueueIntegrationTestJob(0),
            new RedisQueueIntegrationTestJob(1),
            new RedisQueueIntegrationTestJob(2),
            new RedisQueueIntegrationTestJob(3),
        ];

        $this->queue->later(1000, $jobs[0]);
        $this->queue->later(-200, $jobs[1]);
        $this->queue->later(-300, $jobs[2]);
        $this->queue->later(-100, $jobs[3]);

        $this->getPublishClient()
            ->zcard('queues:default:delayed')
            ->then(function ($count) {
                $this->assertEquals(4, $count);
            });

        $this->unregisterManagers();

        $this->assertEquals($jobs[2], unserialize(json_decode($this->queue->pop()->getRawBody())->data->command));
        $this->assertEquals($jobs[1], unserialize(json_decode($this->queue->pop()->getRawBody())->data->command));
        $this->assertEquals($jobs[3], unserialize(json_decode($this->queue->pop()->getRawBody())->data->command));
        $this->assertNull($this->queue->pop());

        $this->assertEquals(1, $this->app['redis']->connection()->zcard('queues:default:delayed'));
        $this->assertEquals(3, $this->app['redis']->connection()->zcard('queues:default:reserved'));
    }

    public function test_release_job()
    {
        $this->queue->push(
            $job = new RedisQueueIntegrationTestJob(30)
        );

        $this->unregisterManagers();

        $this->getPublishClient()
            ->assertCalledCount(1, 'eval');

        $redisJob = $this->queue->pop();

        $before = $this->currentTime();

        $redisJob->release(1000);

        $after = $this->currentTime();

        // check the content of delayed queue
        $this->assertEquals(1, $this->app['redis']->connection()->zcard('queues:default:delayed'));

        $results = $this->app['redis']->connection()->zrangebyscore('queues:default:delayed', -INF, INF, ['withscores' => true]);

        $payload = array_keys($results)[0];

        $score = $results[$payload];

        $this->assertGreaterThanOrEqual($before + 1000, $score);
        $this->assertLessThanOrEqual($after + 1000, $score);

        $decoded = json_decode($payload);

        $this->assertEquals(1, $decoded->attempts);
        $this->assertEquals($job, unserialize($decoded->data->command));

        $this->assertNull($this->queue->pop());
    }

    public function test_delete_job()
    {
        $this->queue->push(
            $job = new RedisQueueIntegrationTestJob(30)
        );

        $this->unregisterManagers();

        $this->getPublishClient()
            ->assertCalledCount(1, 'eval');

        $redisJob = $this->queue->pop();

        $redisJob->delete();

        $this->assertEquals(0, $this->app['redis']->connection()->zcard('queues:default:delayed'));
        $this->assertEquals(0, $this->app['redis']->connection()->zcard('queues:default:reserved'));
        $this->assertEquals(0, $this->app['redis']->connection()->llen('queues:default'));

        $this->assertNull($this->queue->pop());
    }

    public function test_clear_job()
    {
        $job1 = new RedisQueueIntegrationTestJob(30);
        $job2 = new RedisQueueIntegrationTestJob(40);

        $this->queue->push($job1);
        $this->queue->push($job2);

        $this->getPublishClient()
            ->assertCalledCount(2, 'eval');

        $this->unregisterManagers();

        $this->assertEquals(2, $this->queue->clear(null));
        $this->assertEquals(0, $this->queue->size());
    }

    public function test_size_job()
    {
        $this->queue->size()->then(function ($count) {
            $this->assertEquals(0, $count);
        });

        $this->queue->push(new RedisQueueIntegrationTestJob(1));

        $this->queue->size()->then(function ($count) {
            $this->assertEquals(1, $count);
        });

        $this->queue->later(60, new RedisQueueIntegrationTestJob(2));

        $this->queue->size()->then(function ($count) {
            $this->assertEquals(2, $count);
        });

        $this->queue->push(new RedisQueueIntegrationTestJob(3));

        $this->queue->size()->then(function ($count) {
            $this->assertEquals(3, $count);
        });

        $this->unregisterManagers();

        $job = $this->queue->pop();

        $this->registerManagers();

        $this->queue->size()->then(function ($count) {
            $this->assertEquals(3, $count);
        });
    }
}

class RedisQueueIntegrationTestJob
{
    public $i;

    public function __construct($i)
    {
        $this->i = $i;
    }

    public function handle()
    {
        //
    }
}
