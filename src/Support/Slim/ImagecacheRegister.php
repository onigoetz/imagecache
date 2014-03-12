<?php namespace Onigoetz\Imagecache\Support\Slim;

use Onigoetz\Imagecache\Exceptions\InvalidPresetException;
use Onigoetz\Imagecache\Exceptions\NotFoundException;
use Onigoetz\Imagecache\Manager;
use Onigoetz\Imagecache\Transfer;

class ImagecacheRegister
{
    public static function register($app, $config)
    {
        //TODO :: externalize that
        $toolkit = 'gd';

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
                    header('HTTP/1.0 404 Not Found');
                    echo $e->message();
                    exit;
                } catch (NotFoundException $e) {
                    header('HTTP/1.0 404 Not Found');
                    echo $e->message();
                    exit;
                }

                if (!$final_file) {
                    header('HTTP/1.0 500 Internal Server Error');
                    echo 'dunno ...';
                    exit;
                }

                $transfer = new Transfer();
                $transfer->transfer($final_file);
                exit;
            }
        )->conditions(array('file' => '.*'));
    }
}
