<?php namespace Onigoetz\Imagecache\Support\Laravel;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Onigoetz\Imagecache\Exceptions\InvalidPresetException;
use Onigoetz\Imagecache\Exceptions\NotFoundException;
use Onigoetz\Imagecache\Imagekit\Gd;
use Onigoetz\Imagecache\Manager;
use Onigoetz\Imagecache\Transfer;
use Symfony\Component\HttpFoundation\Response;

class ImagecacheServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Add the namespace to config
        $this->app['config']->package('onigoetz/imagecache', __DIR__ . '/../../config');

        $config = $this->app['config']->get('imagecache::imagecache');

        //TODO :: externalize that
        $toolkit = new Gd();

        // Stopwatch - must be registered so the application doesn't fail if the profiler is disabled
        $this->app['imagecache'] = $this->app->share(
            function () use ($config, $toolkit) {
                return new Manager($config, $toolkit);
            }
        );

        //PHP 5.3 compatibility
        $app = $this->app;

        $url = "{$config['path_images']}/{$config['path_cache']}/{preset}/{file}";

        $this->app['router']->get(
            $url,
            function ($preset, $file) use ($app) {
                try {
                    $final_file = $app['imagecache']->handleRequest($preset, $file);
                } catch (InvalidPresetException $e) {
                    return \Response::make('Invalid preset', 404);
                } catch (NotFoundException $e) {
                    return \Response::make('File not found', 404);
                }

                if (!$final_file) {
                    return \Response::make('Dunno what happened', 500);
                }

                $transfer = new Transfer($final_file);

                $callback = function () use ($transfer) {
                    $transfer->stream();
                };

                return \Response::stream($callback, $transfer->getStatus(), $transfer->getFormattedHeaders());
            }
        )->where('file', '.*');
    }
}






