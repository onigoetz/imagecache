<?php namespace Onigoetz\Imagecache\Support\Slim;

use Onigoetz\Imagecache\Exceptions\InvalidPresetException;
use Onigoetz\Imagecache\Exceptions\NotFoundException;
use Onigoetz\Imagecache\Manager;
use Onigoetz\Imagecache\Transfer;
use Slim\Slim;
use RuntimeException;

class ImagecacheRegister
{
    public static function register(Slim $app, $config)
    {
        $app->container->singleton(
            'imagecache',
            function () use ($config) {
                return new Manager($config);
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
                } catch (RuntimeException $e) {
                    $app->response->setStatus(500);
                    $app->response->body($e->getMessage());
                    return;
                }

                $transfer = new Transfer($final_file);
                $app->response->setStatus($transfer->getStatus());
                $app->response->headers->replace($transfer->getHeaders());

                $app->response->setBody(file_get_contents($final_file));
            }
        )
            ->conditions(array('file' => '.*'))
            ->name('onigoetz.imagecache');
    }
}
