<?php namespace Onigoetz\Imagecache\Support\Laravel;

use Illuminate\Support\ServiceProvider;
use Onigoetz\Imagecache\Exceptions\InvalidPresetException;
use Onigoetz\Imagecache\Exceptions\NotFoundException;
use Onigoetz\Imagecache\Manager;
use Onigoetz\Imagecache\Transfer;

class ImagecacheServiceProvider extends ServiceProvider
{
    /**
     * Add the namespace to config
     */
    public function bootConfiguration()
    {
        $this->publishes(
            [
                __DIR__ . '/../../config/imagecache.php' => config_path('imagecache.php'),
            ],
            'config'
        );
    }

    public function registerConfiguration()
    {

        $configPath = __DIR__ . '/../../config/imagecache.php';
        $this->mergeConfigFrom($configPath, 'imagecache');
    }

    public function getConfiguration()
    {
        return $this->app['config']->get('imagecache');
    }

    /**
     * Register imagecache
     */
    public function registerManager()
    {
        $this->app->singleton('imagecache', function () {
            $config = $this->getConfiguration();

            return new Manager($config);
        });
    }

    public function registerRoute()
    {
        $config = $this->getConfiguration();

        $url = "{$config['path_web']}/{$config['path_cache']}/{preset}/{file}";

        $this->app['router']->get(
            $url,
            function ($preset, $file) {
                try {
                    $final_file = $this->app['imagecache']->handleRequest($preset, $file);
                } catch (InvalidPresetException $e) {
                    return \Response::make('Invalid preset', 404);
                } catch (NotFoundException $e) {
                    return \Response::make('File not found', 404);
                } catch (\RuntimeException $e) {
                    return \Response::make($e->getMessage(), 500);
                }

                $transfer = new Transfer($final_file);

                $callback = function () use ($transfer) {
                    $transfer->stream();
                };

                return \Response::stream($callback, $transfer->getStatus(), $transfer->getHeaders());
            }
        )->where('file', '.*');
    }

    public function boot() {
        $this->bootConfiguration();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConfiguration();

        $this->registerManager();

        $this->registerRoute();
    }
}
