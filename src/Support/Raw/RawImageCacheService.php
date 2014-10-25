<?php namespace Onigoetz\Imagecache\Support\Raw;

use Onigoetz\Imagecache\Exceptions\InvalidPresetException;
use Onigoetz\Imagecache\Exceptions\NotFoundException;
use Onigoetz\Imagecache\Imagekit\Gd;
use Onigoetz\Imagecache\Manager;
use Onigoetz\Imagecache\Transfer;

class RawImagecacheService {
    public static function run($config) {
        //TODO :: change that
        $toolkit = new Gd();

        $imagecache = new Manager($config, $toolkit);

        try {
            $final_file = $imagecache->handleRequest($_GET['preset'], $_GET['file']);
        } catch (InvalidPresetException $e) {
            header('HTTP/1.0 404 Not Found');
            echo $e->message();
            return;
        } catch (NotFoundException $e) {
            header('HTTP/1.0 404 Not Found');
            echo $e->message();
            return;
        }

        if (!$final_file) {
            header('HTTP/1.0 500 Internal Server Error');
            echo 'dunno ...';
            return;
        }

        $transfer = new Transfer($final_file);

        if($transfer->getStatus() == 200) {
            header('HTTP/1.1 200 OK');
        } elseif($transfer->getStatus() == 304) {
            header('HTTP/1.1 304 Not Modified');
        }

        foreach($transfer->getFormattedHeaders() as $header) {
            header($header);
        }

        $transfer->stream();
    }
}
