<?php namespace Onigoetz\Imagecache\Support\Slim;

use GuzzleHttp\Psr7\LazyOpenStream;
use Onigoetz\Imagecache\Exceptions\InvalidPresetException;
use Onigoetz\Imagecache\Exceptions\NotFoundException;
use Onigoetz\Imagecache\Manager;
use Onigoetz\Imagecache\Transfer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Slim\App;

class ImagecacheRegister
{
    public function request()
    {
        return function (ServerRequestInterface $req, ResponseInterface $res, $args) {

            $preset = $args['preset'];
            $file = $args['file'];

            try {
                $final_file = $this->get('imagecache')->handleRequest($preset, $file);
            } catch (InvalidPresetException $e) {
                $res->getBody()->write($e->getMessage());
                return $res->withStatus(404);
            } catch (NotFoundException $e) {
                $res->getBody()->write($e->getMessage());
                return $res->withStatus(404);
            } catch (RuntimeException $e) {
                $res->getBody()->write($e->getMessage());
                return $res->withStatus(500);
            }

            $transfer = new Transfer($final_file);
            foreach ($transfer->getHeaders() as $key => $value) {
                $res = $res->withHeader($key, $value);
            }

            return $res->withStatus($transfer->getStatus())->withBody(new LazyOpenStream($final_file, 'r'));
        };
    }

    public static function register(App $app, $config)
    {
        $app->getContainer()->set('imagecache', function () use ($config) {
            return new Manager($config);
        });

        $app->get(
            "/{$config['path_web']}/{$config['path_cache']}/{preset}/{file:.*}",
            (new ImagecacheRegister)->request()
        )
            ->setName('onigoetz.imagecache');
    }
}
