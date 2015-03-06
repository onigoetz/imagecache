<?php namespace Onigoetz\Imagecache\Support\Raw;

use Onigoetz\Imagecache\Exceptions\InvalidPresetException;
use Onigoetz\Imagecache\Exceptions\NotFoundException;
use Onigoetz\Imagecache\Manager;
use Onigoetz\Imagecache\Transfer;

class RawImagecacheService {
    public static function run($config, $request) {
        $imagecache = new Manager($config);

        try {
            $final_file = $imagecache->handleRequest($request['preset'], $request['file']);
        } catch (InvalidPresetException $e) {
            header('HTTP/1.0 404 Not Found');
            echo $e->getMessage();
            return;
        } catch (NotFoundException $e) {
            header('HTTP/1.0 404 Not Found');
            echo $e->getMessage();
            return;
        } catch (\RuntimeException $e) {
            header('HTTP/1.0 500 Internal Server Error');
            echo $e->getMessage();
            return;
        }

        $transfer = new Transfer($final_file);

        // if the status is 304, we don't
        // need to send the content with it
        if ($transfer->getStatus() == 304) {
            header('HTTP/1.1 304 Not Modified');
            return;
        }

        header('HTTP/1.1 200 OK');

        foreach($transfer->getFormattedHeaders() as $header) {
            header($header);
        }

        $transfer->stream();
    }
}
