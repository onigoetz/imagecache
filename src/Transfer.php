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
    protected $path;

    protected $headers = [];
    protected $status = 200;

    public function __construct($path) {
        $this->path = $path;

        $this->getTransferInformations();
    }

    public function getHeaders() {
        return $this->headers;
    }

    public function getFormattedHeaders() {
        $headers = [];

        foreach ($this->headers as $name => $value) {
            $headers[] = "$name: $value";
        }

        return $headers;
    }

    public function getStatus() {
        return $this->status;
    }

    protected function getTransferInformations()
    {
        $size = getimagesize($this->path);
        $this->headers['Content-Type'] = $size['mime'];

        if ($fileinfo = stat($this->path)) {
            $this->headers['Content-Length'] = $fileinfo[7];
            $this->headers['Expires'] = gmdate('D, d M Y H:i:s', time() + 1209600) . ' GMT';
            $this->headers['Cache-Control'] = 'max-age=1209600, private, must-revalidate';
            $this->getCachingHeaders($fileinfo);
        }
    }

    /**
     * Transfer an image to the browser
     */
    public function stream()
    {
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Transfer file in 1024 byte chunks to save memory usage.
        if ($fd = fopen($this->path, 'rb')) {
            while (!feof($fd)) {
                print fread($fd, 1024);
            }
            fclose($fd);
        }
    }

    /**
     * Set file headers that handle "If-Modified-Since" correctly for the
     * given fileinfo.
     *
     * @param array $fileinfo Array returned by stat().
     */
    protected function getCachingHeaders($fileinfo)
    {
        // Set default values:
        $last_modified = gmdate('D, d M Y H:i:s', $fileinfo[9]) . ' GMT';
        $etag = md5($last_modified);

        // See if the client has provided the required HTTP headers:
        $if_modified_since = $this->server_value('HTTP_IF_MODIFIED_SINCE', false);
        $if_none_match = $this->server_value('HTTP_IF_NONE_MATCH', false);

        if ($if_modified_since && $if_none_match && $if_none_match == $etag && $if_modified_since == $last_modified) {
            $this->status = 304;
        }

        // Send appropriate response:
        $this->headers['Last-Modified'] = $last_modified;
        $this->headers['ETag'] = $etag;
    }

    protected function server_value($key, $default)
    {
        return array_key_exists($key, $_SERVER) ? $_SERVER[$key] : $default;
    }
}
