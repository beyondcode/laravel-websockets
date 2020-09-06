<?php

namespace BeyondCode\LaravelWebSockets\Tests\Mocks;

use BeyondCode\LaravelWebSockets\Statistics\Logger\MemoryStatisticsLogger;

class FakeMemoryStatisticsLogger extends MemoryStatisticsLogger
{
    /**
     * {@inheritdoc}
     */
    public function save()
    {
        foreach ($this->statistics as $appId => $statistic) {
            $currentConnectionCount = $this->channelManager->getGlobalConnectionsCount($appId);

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
