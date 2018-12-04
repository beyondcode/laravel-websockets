<?php

namespace BeyondCode\LaravelWebSockets\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class CleanStatistics extends Command
{
    protected $signature = 'websockets:clean
                            {appId? : (optional) The app id that will be cleaned.}';

    protected $description = 'Clean up old statistics from the websocket log.';

    public function handle()
    {
        $this->comment('Cleaning WebSocket Statistics...');

        $appId = $this->argument('appId');

        $maxAgeInDays = config('websockets.statistics.delete_statistics_older_than_days');

        $cutOffDate = Carbon::now()->subDay($maxAgeInDays)->format('Y-m-d H:i:s');

        $webSocketsStatisticsEntryModelClass = config('websockets.statistics.model');

        $amountDeleted = $webSocketsStatisticsEntryModelClass::where('created_at', '<', $cutOffDate)
            ->when(! is_null($appId), function (Builder $query) use ($appId) {
                $query->where('app_id', $appId);
            })
            ->delete();

        $this->info("Deleted {$amountDeleted} record(s) from the WebSocket statistics.");

        $this->comment('All done!');
    }
}
