<?php

namespace BeyondCode\LaravelWebSockets\Database\Console;

use BeyondCode\LaravelWebSockets\Database\Models\App;
use Illuminate\Console\Command;

class AppCreate extends Command
{
    protected $signature = 'websockets:app:create {--name=} {--host=} {--enable_client_messages=0} {--enable_statistics=0}';

    protected $description = 'Create an app and save it on database.';

    public function handle()
    {
        $name = $this->option('name') || $this->ask('What is the app name? (required)');

        if (empty($name)) {
            $this->handle();

            return;
        }

        $host = $this->option('host') || $this->ask('Host: ');

        $enable_client_messages = $this->option('enable_client_messages') || $this->confirm('Would you enable client messages?');

        $enable_statistics = $this->option('enable_statistics') || $this->confirm('Would you enable statistics?');

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
