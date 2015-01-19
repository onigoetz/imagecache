<?php namespace Onigoetz\Imagecache\Support\Laravel;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Onigoetz\Imagecache\Exceptions\InvalidPresetException;
use Onigoetz\Imagecache\Exceptions\NotFoundException;
use Onigoetz\Imagecache\Manager;
use Onigoetz\Imagecache\Transfer;
use Symfony\Component\HttpFoundation\Response;

class ImagecacheServiceProvider extends ServiceProvider
{

    /**
     * Add the namespace to config
     */
    public function registerConfiguration()
    {
        $this->app['config']->package('onigoetz/imagecache', __DIR__ . '/../../config');
    }

    /**
     * Register imagecache
     */
    public function registerManager()
    {
        $this->app['imagecache'] = $this->app->share(
            function () {
                $config = $this->app['config']->get('imagecache::imagecache');

                return new Manager($config);
            }
        );
    }

    public function registerRoute()
    {
        $config = $this->app['config']->get('imagecache::imagecache');

        $url = "{$config['path_images']}/{$config['path_cache']}/{preset}/{file}";

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
                    return \Response::make($e->message(), 500);
                }

                $transfer = new Transfer($final_file);

                $callback = function () use ($transfer) {
                    $transfer->stream();
                };

                return \Response::stream($callback, $transfer->getStatus(), $transfer->getHeaders());
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






