<?php

/**
 * Image Manager
 */

namespace Onigoetz\Imagecache;

/**
 * Image manager
 *
 * Prepares the images for the cache
 *
 * @package Imagecache
 *
 * @author Stéphane Goetz
 */
class Transfer
{
    /**
     * Transfer an image to the browser
     *
     * @param string $path The image to transfer to the browser
     */
    public function transfer($path)
    {
        $size = getimagesize($path);
        $headers = array('Content-Type: ' . $size['mime']);

        if ($fileinfo = stat($path)) {
            $headers[] = 'Content-Length: ' . $fileinfo[7];
            $headers[] = 'Expires: ' . gmdate('D, d M Y H:i:s', time() + 1209600) . ' GMT';
            $headers[] = 'Cache-Control: max-age=1209600, private, must-revalidate';
            $this->set_cache_headers($fileinfo, $headers);
        }

        if (ob_get_level()) {
            ob_end_clean();
        }

        foreach ($headers as $value) {
            header($value);
        }

        // Transfer file in 1024 byte chunks to save memory usage.
        if ($fd = fopen($path, 'rb')) {
            while (!feof($fd)) {
                print fread($fd, 1024);
            }
            fclose($fd);
        }

        exit;
    }

    /**
     * Set file headers that handle "If-Modified-Since" correctly for the
     * given fileinfo.
     *
     * Note that this function may return or may call exit().
     *
     * Most code has been taken from drupal_page_cache_header().
     *
     * @param array $fileinfo Array returned by stat().
     * @param array $headers Array of existing headers.
     */
    private function set_cache_headers($fileinfo, &$headers)
    {
        // Set default values:
        $last_modified = gmdate('D, d M Y H:i:s', $fileinfo[9]) . ' GMT';
        $etag = '"' . md5($last_modified) . '"';

        // See if the client has provided the required HTTP headers:

        $if_modified_since = $this->server_value('HTTP_IF_MODIFIED_SINCE', false);
        $if_none_match = $this->server_value('HTTP_IF_NONE_MATCH', false);

        if ($if_modified_since && $if_none_match
            && $if_none_match == $etag // etag must match
            && $if_modified_since == $last_modified
        ) { // if-modified-since must match
            header('HTTP/1.1 304 Not Modified');
            // All 304 responses must send an etag if the 200 response
            // for the same object contained an etag
            header('Etag: ' . $etag);
            // We must also set Last-Modified again, so that we overwrite the
            // default Last-Modified header with the right one
            header('Last-Modified: ' . $last_modified);
            exit;
        }

        // Send appropriate response:
        $headers[] = 'Last-Modified: ' . $last_modified;
        $headers[] = 'ETag: ' . $etag;
    }

    protected function server_value($key, $default)
    {
        return array_key_exists($key, $_SERVER) ? $_SERVER[$key] : $default;
    }
}
