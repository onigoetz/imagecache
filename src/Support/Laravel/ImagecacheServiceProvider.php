<?php namespace Onigoetz\Imagecache\Support\Laravel;

use Illuminate\Support\ServiceProvider;
use Onigoetz\Imagecache\Exceptions\InvalidPresetException;
use Onigoetz\Imagecache\Exceptions\NotFoundException;
use Onigoetz\Imagecache\Manager;

class ImagecacheServiceProvider extends ServiceProvider
{
    /**
     * Add the namespace to config
     */
    public function registerConfiguration()
    {
        $this->publishes(
            [
                __DIR__ . '/../../config/imagecache.php' => config_path('imagecache.php'),
            ]
        );
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
        $this->app['imagecache'] = $this->app->share(
            function () {
                $config = $this->getConfiguration();

                return new Manager($config);
            }
        );
    }

    public function registerRoute()
    {
        $config = $this->getConfiguration();

        $url = "{$config['path_web']}/{$config['path_cache']}/{preset}/{file}";

        $this->app['router']->get(
            $url,
            function ($preset, $file) {
                try {
                    $image = $this->app['imagecache']->handleRequestAdvanced($preset, $file);
                } catch (InvalidPresetException $e) {
                    return \Response::make('Invalid preset', 404);
                } catch (NotFoundException $e) {
                    return \Response::make('File not found', 404);
                } catch (\RuntimeException $e) {
                    return \Response::make($e->getMessage(), 500);
                }

                return $image->getImage()->response();
            }
        )->where('file', '.*');
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
