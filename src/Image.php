<?php

/**
 * An image to run through imagecache
 */

namespace Onigoetz\Imagecache;

use Onigoetz\Imagecache\Imagekit\Toolkit;

/**
 * An image to run through imagecache
 *
 * @package Imagecache
 */
class Image
{
    /**
     * File source
     * @var String
     */
    public $source;

    /**
     * Which toolkit to use to manage this image
     * @var String
     */
    public $toolkit;

    /**
     * Informations about the image
     * @var Array
     */
    protected $info;

    /**
     * Resource used by GD
     * @var resource
     */
    public $resource;

    /**
     * Create an image and get informations about it
     *
     * @param string $source
     * @param Toolkit $toolkit
     *
     * @throws \Exception if cannot find or load the file
     */
    public function __construct($source, Toolkit $toolkit)
    {
        $this->source = $source;
        $this->toolkit = $toolkit;

        if (!is_file($this->source) && !is_uploaded_file($this->source)) {
            throw new Exceptions\NotFoundException('file not found');
        }

        if (!$this->invoke('load')) {
            throw new \Exception('Cannot load file');
        }
    }

    /**
     * @return String
     */
    public function getToolkit()
    {
        return $this->toolkit;
    }

    /**
     * Invokes the given method using the currently selected toolkit.
     *
     * @param String $method A string containing the method to invoke.
     * @param Array $params An optional array of parameters to pass to the toolkit method.
     *
     * @return mixed Mixed values (typically Boolean indicating successful operation).
     */
    protected function invoke($method, array $params = array())
    {
        array_unshift($params, $this);

        $result = call_user_func_array([$this->getToolkit(), $method], $params);
        $this->info = null;
        return $result;
    }

    /**
     * Get informations and return a single item
     *
     * @param $key
     * @return mixed
     */
    protected function getFromInfo($key)
    {
        $this->getInfo();

        return $this->info[$key];
    }

    /**
     * File's width
     *
     * @return integer
     */
    public function getWidth(){
        return $this->getFromInfo('width');
    }

    /**
     * File's height
     *
     * @return integer
     */
    public function getHeight(){
        return $this->getFromInfo('height');
    }

    /**
     * File's extension
     *
     * @return string
     */
    public function getExtension(){
        return $this->getFromInfo('extension');
    }

    /**
     * File's Mime Type
     *
     * @return string
     */
    public function getMimeType() {
        return $this->getFromInfo('mime_type');
    }

    /**
     * File's size
     *
     * @return integer
     */
    public function getFileSize() {
        return ($this->info != null)? $this->info['file_size'] : filesize($this->source);
    }

    /**
     * Get details about an image.
     *
     * We support GIF, JPG and PNG file formats when used with the GD
     * toolkit, and may support others, depending on which toolkits are
     * installed.
     *
     * @return bool|array false, if the file could not be found or is not an image. Otherwise, a keyed array containing information about the image:
     *   - "width": Width, in pixels.
     *   - "height": Height, in pixels.
     *   - "extension": Commonly used file extension for the image.
     *   - "mime_type": MIME type ('image/jpeg', 'image/gif', 'image/png').
     *   - "file_size": File size in bytes.
     */
    public function getInfo()
    {
        if ($this->info != null) {
            return $this->info;
        }

        $details = $this->invoke('getInfo');
        if (isset($details) && is_array($details)) {
            $details['file_size'] = filesize($this->source);
        }

        return $this->info = $details;
    }

    /**
     * Scales an image to the exact width and height given.
     *
     * This function achieves the target aspect ratio by cropping the original image
     * equally on both sides, or equally on the top and bottom. This function is
     * useful to create uniform sized avatars from larger images.
     *
     * The resulting image always has the exact target dimensions.
     *
     * @param Integer $width The target width, in pixels.
     * @param Integer $height The target height, in pixels.
     *
     * @return bool true or false, based on success.
     *
     * @throws \LogicException if the parameters are wrong
     *
     * @see resize()
     * @see crop()
     */
    public function scale_and_crop($width, $height)
    {
        if ($width === null) {
            throw new \LogicException('"width" must not be null for "scale_and_crop"');
        }

        if ($height === null) {
            throw new \LogicException('"height" must not be null for "scale_and_crop"');
        }

        $scale = max($width / $this->getWidth(), $height / $this->getHeight());
        $x = ($this->getWidth() * $scale - $width) / 2;
        $y = ($this->getHeight() * $scale - $height) / 2;

        if ($this->resize($this->getWidth() * $scale, $this->getHeight() * $scale)) {
            return $this->crop($x, $y, $width, $height);
        }

        return false;
    }

    /**
     * Scales an image to the given width and height while maintaining aspect ratio.
     *
     * The resulting image can be smaller for one or both target dimensions.
     *
     * @param Integer $width
     *   The target width, in pixels. This value is omitted then the scaling will
     *   based only on the height value.
     * @param Integer $height
     *   The target height, in pixels. This value is omitted then the scaling will
     *   based only on the width value.
     * @param Boolean $upscale
     *   Boolean indicating that files smaller than the dimensions will be scaled
     *   up. This generally results in a low quality image.
     *
     * @return bool true or false, based on success.
     *
     * @see scale_and_crop()
     */
    public function scale($width = null, $height = null, $upscale = false)
    {
        $aspect = $this->getHeight() / $this->getWidth();

        if ($upscale) {
            // Set width/height according to aspect ratio if either is empty.
            $width = !empty($width) ? $width : $height / $aspect;
            $height = !empty($height) ? $height : $width / $aspect;
        } else {
            // Set impossibly large values if the width and height aren't set.
            $width = !empty($width) ? $width : 9999999;
            $height = !empty($height) ? $height : 9999999;

            // Don't scale up.
            if (round($width) >= $this->getWidth() && round($height) >= $this->getHeight()) {
                return true;
            }
        }

        if ($aspect < $height / $width) {
            $height = $width * $aspect;
        } else {
            $width = $height / $aspect;
        }

        return $this->resize($width, $height);
    }

    /**
     * Resize an image to the given dimensions (ignoring aspect ratio).
     *
     * @param Integer $width The target width, in pixels.
     * @param Integer $height The target height, in pixels.
     *
     * @return bool true or false, based on success.
     *
     * @see gd_resize()
     */
    public function resize($width, $height)
    {
        $width = (int)round($width);
        $height = (int)round($height);

        return $this->invoke('resize', array($width, $height));
    }

    /**
     * Rotate an image by the given number of degrees.
     *
     * @param  int $degrees The number of (clockwise) degrees to rotate the image.
     * @param  string|null $background hexadecimal background color
     * @param  bool $random
     * @return bool     true or false, based on success.
     */
    public function rotate($degrees, $background = null, $random = false)
    {
        // Set sane default values.
        if (strlen(trim($background))) {
            $background = hexdec(str_replace('#', '', $background));
        }

        if ($random) {
            $deg = abs((float)$degrees);
            $degrees = rand(-1 * $deg, $deg);
        }

        return $this->invoke('rotate', array($degrees, $background));
    }

    /**
     * Crop an image to the rectangle specified by the given rectangle.
     *
     * @param Integer $xoffset
     *   The top left coordinate, in pixels, of the crop area (x axis value).
     * @param Integer $yoffset
     *   The top left coordinate, in pixels, of the crop area (y axis value).
     * @param Integer $width
     *   The target width, in pixels.
     * @param Integer $height
     *   The target height, in pixels.
     *
     * @return bool true or false, based on success.
     *
     * @throws \LogicException if the parameters are wrong
     *
     * @see scale_and_crop()
     * @see gd_crop()
     */
    public function crop($xoffset, $yoffset, $width, $height)
    {
        if ($xoffset === null) {
            throw new \LogicException('"xoffset" must not be null for "crop"');
        }

        if ($yoffset === null) {
            throw new \LogicException('"yoffset" must not be null for "crop"');
        }

        if ($width === null) {
            throw new \LogicException('"width" must not be null for "crop"');
        }

        if ($height === null) {
            throw new \LogicException('"height" must not be null for "crop"');
        }

        $width = (int)round($width);
        $height = (int)round($height);

        return $this->invoke('crop', array($xoffset, $yoffset, $width, $height));
    }

    /**
     * Convert an image to grayscale.
     *
     * @return bool true or false, based on success.
     *
     * @see gd_desaturate()
     */
    public function desaturate()
    {
        return $this->invoke('desaturate');
    }

    /**
     * Close the image and save the changes to a file.
     *
     * @param  string|null $destination Destination path where the image should be saved. If it is empty the original image file will be overwritten.
     * @return bool|Image  image or false, based on success.
     */
    public function save($destination = null)
    {
        if (empty($destination)) {
            $destination = $this->source;
        }

        if (!$this->invoke('save', array($destination))) {
            return false;
        }

        // Clear the cached file size and refresh the image information.
        clearstatcache();

        chmod($destination, 0644);

        return new Image($destination, $this->toolkit);
    }
}
