<?php

/**
 * An image to run through imagecache
 */
namespace Onigoetz\Imagecache;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

/**
 * An image to run through imagecache
 */
class Image
{
    /**
     * File source
     * @var string
     */
    public $source;

    /**
     * Informations about the image
     * @var array
     */
    protected $info;

    /**
     * Image resource
     * @var \Intervention\Image\Image
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

        // create image manager with desired driver
        $manager = new ImageManager(new Driver());

        $this->image = $manager->read($source);
    }

    /**
     * @return \Intervention\Image\Image
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param \Intervention\Image\Image $image
     * used mainly for unit tests, we cannot typehint as we get a mockery object
     */
    public function setImage($image)
    {
        $this->image = $image;
    }

    /**
     * File's width
     *
     * @return int
     */
    public function getWidth()
    {
        return $this->image->width();
    }

    /**
     * File's height
     *
     * @return int
     */
    public function getHeight()
    {
        return $this->image->height();
    }

    /**
     * File's size
     *
     * @return int
     */
    public function getFileSize()
    {
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
        $this->info = [
            'width' => $this->getWidth(),
            'height' => $this->getHeight(),
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
     * @param int $width The target width, in pixels.
     * @param int $height The target height, in pixels.
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
    }

    /**
     * Scales an image to the given width and height while maintaining aspect ratio.
     *
     * The resulting image can be smaller for one or both target dimensions.
     *
     * @param int $width
     *   The target width, in pixels. This value is omitted then the scaling will
     *   based only on the height value.
     * @param int $height
     *   The target height, in pixels. This value is omitted then the scaling will
     *   based only on the width value.
     */
    public function scale($width = null, $height = null)
    {
        if ($width === null && $height === null) {
            throw new \LogicException('one of "width" or "height" must be set for "scale"');
        }

        $this->image->scale($width, $height);
    }

    /**
     * Resize an image to the given dimensions (ignoring aspect ratio).
     *
     * @param int $width The target width, in pixels.
     * @param int $height The target height, in pixels.
     */
    public function resize($width, $height)
    {
        $this->image->resize($width, $height);
    }

    /**
     * Rotate an image by the given number of degrees.
     *
     * @param  int $degrees The number of (clockwise) degrees to rotate the image.
     * @param  string|null $background hexadecimal background color
     * @param  bool $random
     */
    public function rotate($degrees, $background = null, $random = false)
    {
        if ($background) {
            $background = trim($background);
        } else {
            $background = "ffffff";
        }

        if ($random) {
            $deg = abs((float) $degrees);
            $degrees = rand(-1 * $deg, $deg);
        }

        $this->image->rotate($degrees, $background);
    }

    /**
     * Crop an image to the rectangle specified by the given rectangle.
     *
     * @param int $xoffset
     *   The top left coordinate, in pixels, of the crop area (x axis value).
     * @param int $yoffset
     *   The top left coordinate, in pixels, of the crop area (y axis value).
     * @param int $width
     *   The target width, in pixels.
     * @param int $height
     *   The target height, in pixels.
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

        $this->image->crop($width, $height, $xoffset, $yoffset);
    }

    /**
     * Convert an image to grayscale.
     */
    public function desaturate()
    {
        $this->image->greyscale();
    }

    /**
     * Close the image and save the changes to a file.
     *
     * @param  string|null $destination Destination path where the image should be saved. If it is empty the original image file will be overwritten.
     * @throws \RuntimeException
     * @return Image  image or false, based on success.
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

        return new self($destination);
    }
}
