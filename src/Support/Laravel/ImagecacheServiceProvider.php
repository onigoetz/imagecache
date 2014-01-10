<?php namespace Onigoetz\Imagecache\Support\Laravel;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpFoundation\Response;

class ProfilerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->package('onigoetz/imagecache');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $config = $app['config']->get('imagecache');
            
        //TODO :: externalize that
        $toolkit = 'gd';

        // Stopwatch - must be registered so the application doesn't fail if the profiler is disabled
        $this->app['imagecache'] = $this->app->share(
            function () use ($config, $toolkit) {
                return new Manager($config, $toolkit);
            }
        );
        
        //PHP 5.3 compatibility
        $app = $this->app;
        
        $image_handler = 
        
        $url = "{$options['path_images']}/{$options['path_cache']}/{preset}/{file}";
        
        Route::get(
            $url,
            function ($preset, $file) use ($app) {
                try {
                    $final_file = $app['imagecache']->handle_request($preset, $file)
                } catch (Exceptions\InvalidPresetException $e) {
                    return Response::make('Invalid preset', 404);
                } catch (Exceptions\NotFoundException $e) {
                    return Response::make('File not found', 404);
                }

                if(!$final_file) {
                    return Response::make('Dunno what happened', 500);
                }
            
                //TODO :: be more "symfony reponse" friendly
                $transfer = new Transfer();
                $transfer->transfer($final_file);
                exit;
            }
        )->where('file', '.*');
    }
}






