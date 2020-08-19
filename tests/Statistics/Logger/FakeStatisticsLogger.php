<?php

namespace BeyondCode\LaravelWebSockets\Tests\Statistics\Logger;

use BeyondCode\LaravelWebSockets\Statistics\Logger\MemoryStatisticsLogger;

class FakeStatisticsLogger extends MemoryStatisticsLogger
{
    /**
     * {@inheritdoc}
     */
    public function save()
    {
        foreach ($this->statistics as $appId => $statistic) {
            $currentConnectionCount = $this->channelManager->getConnectionCount($appId);
            $statistic->reset($currentConnectionCount);
        }
    }

    /**
     * Get app by id.
     *
     * @param  mixed  $appId
     * @return array
     */
    public function getForAppId($appId): array
    {
        $statistic = $this->findOrMakeStatisticForAppId($appId);

        return $statistic->toArray();
    }
}
