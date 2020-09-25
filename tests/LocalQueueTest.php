<?php

namespace BeyondCode\LaravelWebSockets\Test;

use Illuminate\Container\Container;
use Illuminate\Contracts\Redis\Factory;
use Illuminate\Queue\Jobs\RedisJob;
use Illuminate\Queue\LuaScripts;
use Illuminate\Queue\Queue;
use Illuminate\Queue\RedisQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Mockery as m;
use stdClass;

class LocalQueueTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->runOnlyOnLocalReplication();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        m::close();
    }

    public function testPushProperlyPushesJobOntoRedis()
    {
        $uuid = Str::uuid();

        Str::createUuidsUsing(function () use ($uuid) {
            return $uuid;
        });

        $queue = $this->getMockBuilder(RedisQueue::class)
            ->setMethods(['getRandomId'])
            ->setConstructorArgs([$redis = m::mock(Factory::class), 'default'])
            ->getMock();

        $queue->expects($this->once())
            ->method('getRandomId')
            ->willReturn('foo');

        $redis->shouldReceive('connection')
            ->once()
            ->andReturn($redis);

        $redis->shouldReceive('eval')
            ->once()
            ->with(LuaScripts::push(), 2, 'queues:default', 'queues:default:notify', json_encode(['uuid' => $uuid, 'displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'maxExceptions' => null, 'backoff' => null, 'timeout' => null, 'data' => ['data'], 'id' => 'foo', 'attempts' => 0]));

        $id = $queue->push('foo', ['data']);

        $this->assertSame('foo', $id);

        Str::createUuidsNormally();
    }

    public function testPushProperlyPushesJobOntoRedisWithCustomPayloadHook()
    {
        $uuid = Str::uuid();

        Str::createUuidsUsing(function () use ($uuid) {
            return $uuid;
        });

        $queue = $this->getMockBuilder(RedisQueue::class)
            ->setMethods(['getRandomId'])
            ->setConstructorArgs([$redis = m::mock(Factory::class), 'default'])
            ->getMock();

        $queue->expects($this->once())
            ->method('getRandomId')
            ->willReturn('foo');

        $redis->shouldReceive('connection')
            ->once()
            ->andReturn($redis);

        $redis->shouldReceive('eval')
            ->once()
            ->with(LuaScripts::push(), 2, 'queues:default', 'queues:default:notify', json_encode(['uuid' => $uuid, 'displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'maxExceptions' => null, 'backoff' => null, 'timeout' => null, 'data' => ['data'], 'custom' => 'taylor', 'id' => 'foo', 'attempts' => 0]));

        Queue::createPayloadUsing(function ($connection, $queue, $payload) {
            return ['custom' => 'taylor'];
        });

        $id = $queue->push('foo', ['data']);

        $this->assertSame('foo', $id);

        Queue::createPayloadUsing(null);

        Str::createUuidsNormally();
    }

    public function testPushProperlyPushesJobOntoRedisWithTwoCustomPayloadHook()
    {
        $uuid = Str::uuid();

        Str::createUuidsUsing(function () use ($uuid) {
            return $uuid;
        });

        $queue = $this->getMockBuilder(RedisQueue::class)
            ->setMethods(['getRandomId'])
            ->setConstructorArgs([$redis = m::mock(Factory::class), 'default'])
            ->getMock();

        $queue->expects($this->once())
            ->method('getRandomId')
            ->willReturn('foo');

        $redis->shouldReceive('connection')
            ->once()
            ->andReturn($redis);

        $redis->shouldReceive('eval')
            ->once()
            ->with(LuaScripts::push(), 2, 'queues:default', 'queues:default:notify', json_encode(['uuid' => $uuid, 'displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'maxExceptions' => null, 'backoff' => null, 'timeout' => null, 'data' => ['data'], 'custom' => 'taylor', 'bar' => 'foo', 'id' => 'foo', 'attempts' => 0]));

        Queue::createPayloadUsing(function ($connection, $queue, $payload) {
            return ['custom' => 'taylor'];
        });

        Queue::createPayloadUsing(function ($connection, $queue, $payload) {
            return ['bar' => 'foo'];
        });

        $id = $queue->push('foo', ['data']);

        $this->assertSame('foo', $id);

        Queue::createPayloadUsing(null);

        Str::createUuidsNormally();
    }

    public function testDelayedPushProperlyPushesJobOntoRedis()
    {
        $uuid = Str::uuid();

        Str::createUuidsUsing(function () use ($uuid) {
            return $uuid;
        });

        $queue = $this->getMockBuilder(RedisQueue::class)
            ->setMethods(['availableAt', 'getRandomId'])
            ->setConstructorArgs([$redis = m::mock(Factory::class), 'default'])
            ->getMock();

        $queue->expects($this->once())
            ->method('getRandomId')
            ->willReturn('foo');

        $queue->expects($this->once())
            ->method('availableAt')
            ->with(1)
            ->willReturn(2);

        $redis->shouldReceive('connection')
            ->once()
            ->andReturn($redis);

        $redis->shouldReceive('zadd')
            ->once()
            ->with('queues:default:delayed', 2, json_encode(['uuid' => $uuid, 'displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'maxExceptions' => null, 'backoff' => null, 'timeout' => null, 'data' => ['data'], 'id' => 'foo', 'attempts' => 0]));

        $id = $queue->later(1, 'foo', ['data']);

        $this->assertSame('foo', $id);

        Str::createUuidsNormally();
    }

    public function testDelayedPushWithDateTimeProperlyPushesJobOntoRedis()
    {
        $uuid = Str::uuid();

        Str::createUuidsUsing(function () use ($uuid) {
            return $uuid;
        });

        $date = Carbon::now();

        $queue = $this->getMockBuilder(RedisQueue::class)
            ->setMethods(['availableAt', 'getRandomId'])
            ->setConstructorArgs([$redis = m::mock(Factory::class), 'default'])
            ->getMock();

        $queue->expects($this->once())
            ->method('getRandomId')
            ->willReturn('foo');

        $queue->expects($this->once())
            ->method('availableAt')
            ->with($date)
            ->willReturn(2);

        $redis->shouldReceive('connection')
            ->once()
            ->andReturn($redis);

        $redis->shouldReceive('zadd')
            ->once()
            ->with('queues:default:delayed', 2, json_encode(['uuid' => $uuid, 'displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'maxExceptions' => null, 'backoff' => null, 'timeout' => null, 'data' => ['data'], 'id' => 'foo', 'attempts' => 0]));

        $queue->later($date, 'foo', ['data']);

        Str::createUuidsNormally();
    }

    public function testFireProperlyCallsTheJobHandler()
    {
        $job = $this->getJob();

        $job->getContainer()
            ->shouldReceive('make')
            ->once()->with('foo')
            ->andReturn($handler = m::mock(stdClass::class));

        $handler->shouldReceive('fire')
            ->once()
            ->with($job, ['data']);

        $job->fire();
    }

    public function testDeleteRemovesTheJobFromRedis()
    {
        $job = $this->getJob();

        $job->getRedisQueue()
            ->shouldReceive('deleteReserved')
            ->once()
            ->with('default', $job);

        $job->delete();
    }

    public function testReleaseProperlyReleasesJobOntoRedis()
    {
        $job = $this->getJob();

        $job->getRedisQueue()
            ->shouldReceive('deleteAndRelease')
            ->once()
            ->with('default', $job, 1);

        $job->release(1);
    }

    protected function getJob()
    {
        return new RedisJob(
            m::mock(Container::class),
            m::mock(RedisQueue::class),
            json_encode(['job' => 'foo', 'data' => ['data'], 'attempts' => 1]),
            json_encode(['job' => 'foo', 'data' => ['data'], 'attempts' => 2]),
            'connection-name',
            'default'
        );
    }
}
