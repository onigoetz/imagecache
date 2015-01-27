<?php

/**
 * An image to run through imagecache
 */

namespace Onigoetz\Imagecache;

use Imagine\Filter\Advanced\Grayscale;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;
use RuntimeException;

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
     * Informations about the image
     * @var Array
     */
    protected $info;

    /**
     * Image resource
     * @var \Imagine\Image\ImageInterface
     */
    protected $image;

    /**
     * Create an image and get informations about it
     *
     * @param string $source
     *
     * @throws \Exception if cannot find or load the file
     */
    public function __construct($source)
    {
        $this->source = $source;

        if (!is_file($this->source) && !is_uploaded_file($this->source)) {
            throw new Exceptions\NotFoundException('file not found');
        }

        $imagine = $this->getImagine();
        $this->image = $imagine->open($source);
    }

    /**
     * @return ImageInterface
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param ImageInterface $image
     * used mainly for unit tests, we cannot typehint as we get a mockery object
     */
    public function setImage($image)
    {
        $this->image = $image;
    }

    /**
     * @return \Imagine\Image\ImagineInterface
     * @codeCoverageIgnore This method is linked to the system, so we can't test it correctly
     */
    protected function getImagine() {
        try {
            return new \Imagine\Gd\Imagine();
        } catch(RuntimeException $noGd) {
            try {
                return new \Imagine\Imagick\Imagine();
            } catch(RuntimeException $noImagick) {
                try {
                    return new \Imagine\Gmagick\Imagine();
                } catch(RuntimeException $noGmagick) {
                    throw new RuntimeException("none of Gd, Imagick or Gmagick are available on this setup");
                }
            }
        }
    }

    /**
     * File's width
     *
     * @return integer
     */
    public function getWidth(){
        return $this->image->getSize()->getWidth();
    }

    /**
     * File's height
     *
     * @return integer
     */
    public function getHeight(){
        return $this->image->getSize()->getHeight();
    }

    /**
     * File's size
     *
     * @return integer
     */
    public function getFileSize() {
        return filesize($this->source);
    }

    /**
     * Get details about an image.
     *
     * @return bool|array false, if the file could not be found or is not an image. Otherwise, a keyed array containing information about the image:
     *   - "width": Width, in pixels.
     *   - "height": Height, in pixels.
     *   - "file_size": File size in bytes.
     */
    public function getInfo()
    {
        $size = $this->image->getSize();

        $this->info = [
            'width' => $size->getWidth(),
            'height' => $size->getHeight(),
            'file_size' => $this->getFileSize(),
        ];

        return $this->info;
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
        $w = ceil($this->getWidth() * $scale);
        $h = ceil($this->getHeight() * $scale);
        $x = ($w - $width) / 2;
        $y = ($h - $height) / 2;

        $this->resize($w, $h);
        $this->crop($x, $y, $width, $height);

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
     */
    public function scale($width = null, $height = null)
    {
        if ($width == null && $height == null) {
            throw new \LogicException('one of "width" or "height" must be set for "scale"');
        }

        if ($width != null && $height != null) {
            $this->resize($width, $height);
        }

        $size = $this->image->getSize();
        $size = ($width != null)? $size->widen($width) : $size->heighten($height);

        $this->image->resize($size);
    }

    /**
     * Resize an image to the given dimensions (ignoring aspect ratio).
     *
     * @param Integer $width The target width, in pixels.
     * @param Integer $height The target height, in pixels.
     *
     * @return bool true or false, based on success.
     */
    public function resize($width, $height)
    {
        $this->image->resize(new Box($width, $height));
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
        $palette = new \Imagine\Image\Palette\RGB();

        if (strlen(trim($background))) {
            $background = $palette->color($background, 0);
        }

        // by default the background is transparent if supported
        if (!$background && $palette->supportsAlpha()) {
            $background = $palette->color('fff', 100);
        }

        if ($random) {
            $deg = abs((float)$degrees);
            $degrees = rand(-1 * $deg, $deg);
        }

        $this->image->rotate($degrees, $background);
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

        $start = new Point($xoffset, $yoffset);
        $size = new Box($width, $height);

        $this->image->crop($start, $size);
    }

    /**
     * Convert an image to grayscale.
     */
    public function desaturate()
    {
        (new Grayscale())->apply($this->image);
    }

    /**
     * Close the image and save the changes to a file.
     *
     * @param  string|null $destination Destination path where the image should be saved. If it is empty the original image file will be overwritten.
     * @return Image  image or false, based on success.
     * @throws \RuntimeException
     */
    public function save($destination = null)
    {
        if (empty($destination)) {
            $destination = $this->source;
        }

        $this->image->save($destination);

        // Clear the cached file size and refresh the image information.
        clearstatcache();

        chmod($destination, 0644);

        return new Image($destination);
    }
}
