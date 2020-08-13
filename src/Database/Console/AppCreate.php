<?php

namespace BeyondCode\LaravelWebSockets\Database\Console;

use Illuminate\Console\Command;
use BeyondCode\LaravelWebSockets\Database\Models\App;

class AppCreate extends Command
{
    protected $signature = 'websockets:app:create';

    protected $description = 'Create an app and save it on database.';

    public function handle()
    {
        $name = $this->ask('What is the app name? (required)');

        if (empty($name)) {
            $this->handle();

            return;
        }

        $host = $this->ask('Host: ');

        $enable_client_messages = $this->confirm('Would you enable client messages?');

        $enable_statistics = $this->confirm('Would you enable statistics?');

        $this->comment('Creating your application, please wait...');

        $app = App::create([
            'name' => $name,
            'host' => $host,
            'enable_client_messages' => $enable_client_messages,
            'enable_statistics' => $enable_statistics,
        ]);

        $this->info('Key: '.$app->key);
        $this->info('Secret: '.$app->secret);

        $this->comment('App has been created. Please save key and secret.');
    }
}
