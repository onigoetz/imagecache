<?php namespace Onigoetz\Imagecache\Support\Slim;

use Onigoetz\Imagecache\Exceptions\InvalidPresetException;
use Onigoetz\Imagecache\Exceptions\NotFoundException;
use Onigoetz\Imagecache\Imagekit\Gd;
use Onigoetz\Imagecache\Manager;
use Onigoetz\Imagecache\Transfer;
use Slim\Slim;

class ImagecacheRegister
{
    public static function register(Slim $app, $config)
    {
        //TODO :: externalize that
        $toolkit = new Gd();

        $app->container->singleton(
            'imagecache',
            function () use ($config, $toolkit) {
                return new Manager($config, $toolkit);
            }
        );

        $url = "/{$config['path_images']}/{$config['path_cache']}/:preset/:file";

        $app->get(
            $url,
            function ($preset, $file) use ($app) {
                try {
                    $final_file = $app->imagecache->handleRequest($preset, $file);
                } catch (InvalidPresetException $e) {
                    $app->response->setStatus(404);
                    $app->response->body($e->getMessage());
                    return;
                } catch (NotFoundException $e) {
                    $app->response->setStatus(404);
                    $app->response->body($e->getMessage());
                    return;
                }

                if (!$final_file) {
                    $app->response->setStatus(500);
                    $app->response->body('some error occured');
                    return;
                }

                $transfer = new Transfer($final_file);

                $app->response->setStatus($transfer->getStatus());
                $app->response->headers->replace($transfer->getHeaders());

                $app->response->body(file_get_contents($final_file));

                //TODO :: maybe find a way to stream with slim and be testable
                //$transfer->stream($final_file);
            }
        )
            ->conditions(array('file' => '.*'))
            ->name('onigoetz.imagecache');
    }
}
