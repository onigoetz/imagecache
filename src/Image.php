<?php

/**
 * An image to run through imagecache
 */

namespace Onigoetz\Imagecache;

use ReflectionMethod;

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
     * @param string $toolkit
     */
    public function __construct($source, $toolkit)
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

    public function call($method, $args)
    {
        if (!method_exists($this, $method)) {
            throw new \LogicException("Method '$method' doesn't exist");
        }

        $reflected = new ReflectionMethod(__CLASS__, $method);
        $parameters = $reflected->getParameters();

        $arguments = array();
        foreach ($parameters as $param) {
            if (array_key_exists($param->name, $args)) {
                $arguments[$param->name] = $args[$param->name];
            } else {
                $arguments[$param->name] = ($param->isOptional()) ? $param->getDefaultValue() : null;
            }
        }

        return call_user_func_array(array($this, $method), $arguments);
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
        $function = array('Onigoetz\Imagecache\\Imagekit\\' . ucfirst($this->toolkit), $method);
        if (method_exists($function[0], $function[1])) {
            array_unshift($params, $this);

            $result = call_user_func_array($function, $params);
            $this->info = null;
            return $result;
        }

        return false;
    }

    public function getWidth(){
        if ($this->info == null) {
            $this->getInfo();
        }

        return $this->info['width'];
    }

    public function getHeight(){
        if ($this->info == null) {
            $this->getInfo();
        }

        return $this->info['height'];
    }

    /**
     * @todo Can be cleaner
     *
     * @return mixed
     */
    public function getExtension(){
        if ($this->info == null) {
            $this->getInfo();
        }

        return $this->info['extension'];
    }

    public function getMimeType() {
        if ($this->info == null) {
            $this->getInfo();
        }

        return $this->info['mime_type'];
    }

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

        $details = $this->invoke('get_info');
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

        if ($this->invoke('save', array($destination))) {
            // Clear the cached file size and refresh the image information.
            clearstatcache();

            if (chmod($destination, 0644)) {
                return new Image($destination, $this->toolkit);
            }
        }

        return false;
    }
}
