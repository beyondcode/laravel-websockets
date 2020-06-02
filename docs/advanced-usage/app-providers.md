# Custom App Providers

With the multi-tenancy support of Laravel WebSockets, the default way of storing and retrieving the apps is by using the `websockets.php` config file.

Depending on your setup, you might have your app configuration stored elsewhere and having to keep the configuration in sync with your app storage can be tedious. To simplify this, you can create your own `AppProvider` class that will take care of retrieving the WebSocket credentials for a specific WebSocket application.

> Make sure that you do **not** perform any IO blocking tasks in your `AppProvider`, as they will interfere with the asynchronous WebSocket execution.

In order to create your custom `AppProvider`, create a class that implements the `BeyondCode\LaravelWebSockets\AppProviders\AppProvider` interface.

This is what it looks like:

```php
interface AppProvider
{
    /**  @return array[BeyondCode\LaravelWebSockets\AppProviders\App] */
    public function all(): array;

    /**  @return BeyondCode\LaravelWebSockets\AppProviders\App */
    public function findById($appId): ?App;

    /**  @return BeyondCode\LaravelWebSockets\AppProviders\App */
    public function findByKey(string $appKey): ?App;

    /**  @return BeyondCode\LaravelWebSockets\AppProviders\App */
    public function findBySecret(string $appSecret): ?App;
}
```

The following is an example AppProvider that utilizes an Eloquent model:
```php
namespace App\Providers;

use App\Application;
use BeyondCode\LaravelWebSockets\Apps\App;
use BeyondCode\LaravelWebSockets\Apps\AppProvider;

class MyCustomAppProvider implements AppProvider
{
    public function all() : array
    {
        return Application::all()
            ->map(function($app) {
                return $this->instanciate($app->toArray());
            })
            ->toArray();
    }

    public function findById($appId) : ? App
    {
        return $this->instanciate(Application::findById($appId)->toArray());
    }

    public function findByKey(string $appKey) : ? App
    {
        return $this->instanciate(Application::findByKey($appKey)->toArray());
    }

    public function findBySecret(string $appSecret) : ? App
    {
        return $this->instanciate(Application::findBySecret($appSecret)->toArray());
    }

    protected function instanciate(?array $appAttributes) : ? App
    {
        if (!$appAttributes) {
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

Once you have implemented your own AppProvider, you need to set it in the `websockets.php` configuration file:

```php	
/**
 * This class is responsible for finding the apps. The default provider
 * will use the apps defined in this config file.
 *
 * You can create a custom provider by implementing the
 * `AppProvider` interface.
 */
'app_provider' => MyCustomAppProvider::class,
```
