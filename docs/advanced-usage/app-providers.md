---
title: Custom App Managers
order: 1
---

# Custom App Managers

With the multi-tenancy support of Laravel WebSockets, the default way of storing and retrieving the apps is by using the `websockets.php` config file.

Depending on your setup, you might have your app configuration stored elsewhere and having to keep the configuration in sync with your app storage can be tedious. To simplify this, you can create your own `AppManager` class that will take care of retrieving the WebSocket credentials for a specific WebSocket application.

> Make sure that you do **not** perform any IO blocking tasks in your `AppManager`, as they will interfere with the asynchronous WebSocket execution.

In order to create your custom `AppManager`, create a class that implements the `BeyondCode\LaravelWebSockets\Contracts\AppManager` interface.

This is what it looks like:

```php
interface AppManager
{
    /**  @return array[BeyondCode\LaravelWebSockets\Apps\App] */
    public function all(): array;

    /**  @return BeyondCode\LaravelWebSockets\Apps\App */
    public function findById($appId): ?App;

    /**  @return BeyondCode\LaravelWebSockets\Apps\App */
    public function findByKey($appKey): ?App;

    /**  @return BeyondCode\LaravelWebSockets\Apps\App */
    public function findBySecret($appSecret): ?App;
}
```

The following is an example AppManager that utilizes an Eloquent model:
```php
namespace App\Managers;

use App\Application;
use BeyondCode\LaravelWebSockets\Apps\App;
use BeyondCode\LaravelWebSockets\Contracts\AppManager;

class MyCustomAppManager implements AppManager
{
    public function all() : array
    {
        return Application::all()
            ->map(function($app) {
                return $this->normalize($app->toArray());
            })
            ->toArray();
    }

    public function findById($appId) : ?App
    {
        return $this->normalize(Application::findById($appId)->toArray());
    }

    public function findByKey($appKey) : ?App
    {
        return $this->normalize(Application::findByKey($appKey)->toArray());
    }

    public function findBySecret($appSecret) : ?App
    {
        return $this->normalize(Application::findBySecret($appSecret)->toArray());
    }

    protected function normalize(?array $appAttributes) : ?App
    {
        if (! $appAttributes) {
            return null;
        }

        $app = new App(
            $appAttributes['id'],
            $appAttributes['key'],
            $appAttributes['secret']
        );

        if (isset($appAttributes['name'])) {
            $app->setName($appAttributes['name']);
        }

        if (isset($appAttributes['host'])) {
            $app->setHost($appAttributes['host']);
        }

        $app
            ->enableClientMessages($appAttributes['enable_client_messages'])
            ->enableStatistics($appAttributes['enable_statistics']);

        return $app;
    }
}
```

Once you have implemented your own AppManager, you need to set it in the `websockets.php` configuration file:

```php
'managers' => [

    /*
    |--------------------------------------------------------------------------
    | Application Manager
    |--------------------------------------------------------------------------
    |
    | An Application manager determines how your websocket server allows
    | the use of the TCP protocol based on, for example, a list of allowed
    | applications.
    | By default, it uses the defined array in the config file, but you can
    | anytime implement the same interface as the class and add your own
    | custom method to retrieve the apps.
    |
    */

    'app' => \App\Managers\MyCustomAppManager::class,

],
```
