<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Events;

use BeyondCode\LaravelWebSockets\Dashboard\DashboardLogger;
use BeyondCode\LaravelWebSockets\Statistics\Drivers\StatisticsDriver;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class StatisticsUpdated implements ShouldBroadcast
{
    use SerializesModels;

    /**
     * The statistics driver instance.
     *
     * @var \BeyondCode\LaravelWebSockets\Statistics\Drivers\StatisticsDriver
     */
    protected $driver;

    /**
     * Initialize the event.
     *
     * @param  \BeyondCode\LaravelWebSockets\Statistics\Drivers\StatisticsDriver  $driver
     * @return void
     */
    public function __construct(StatisticsDriver $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Format the broadcasting message.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'time' => $this->driver->getTime(),
            'app_id' => $this->driver->getAppId(),
            'peak_connection_count' => $this->driver->getPeakConnectionCount(),
            'websocket_message_count' => $this->driver->getWebsocketMessageCount(),
            'api_message_count' => $this->driver->getApiMessageCount(),
        ];
    }

    /**
     * Specify the channel to broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel
     */
    public function broadcastOn()
    {
        $channelName = Str::after(DashboardLogger::LOG_CHANNEL_PREFIX.'statistics', 'private-');

        return new PrivateChannel(
            Str::after(DashboardLogger::LOG_CHANNEL_PREFIX.'statistics', 'private-')
        );
    }

    /**
     * Define the broadcasted event name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'statistics-updated';
    }
}
