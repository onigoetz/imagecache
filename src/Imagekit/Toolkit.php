<?php
/**
 * Created by IntelliJ IDEA.
 * User: sgoetz
 * Date: 15.08.14
 * Time: 16:47
 */

namespace Onigoetz\Imagecache\Imagekit;


use Onigoetz\Imagecache\Image;

interface Toolkit
{
    /**
     * Scale an image to the specified size using GD.
     *
     * @param  Image $image An image object. The $image->resource, $image->info['width'], and $image->info['height'] values will be modified by this call.
     * @param  int $width The new width of the resized image, in pixels.
     * @param  int $height The new height of the resized image, in pixels.
     * @return bool  true or false, based on success.
     */
    public function resize(Image $image, $width, $height);

    /**
     * Rotate an image the given number of degrees.
     *
     * @param  Image $image An image object. The $image->resource, $image->info['width'], and $image->info['height'] values will be modified by this call.
     * @param  int $degrees The number of (clockwise) degrees to rotate the image.
     * @param  null $background An hexadecimal integer specifying the background color to use for the uncovered area of the image after the rotation. E.g. 0x000000 for black, 0xff00ff for magenta, and 0xffffff for white. For images that support transparency, this will default to transparent. Otherwise it will be white.
     * @return bool  true or false, based on success.
     */
    public function rotate(Image $image, $degrees, $background = null);

    /**
     * Crop an image using the GD toolkit.
     *
     * @param  Image $image An image object. The $image->resource, $image->info['width'], and $image->info['height'] values will be modified by this call.
     * @param  int $x The starting x offset at which to start the crop, in pixels.
     * @param  int $y The starting y offset at which to start the crop, in pixels.
     * @param  int $width The width of the cropped area, in pixels.
     * @param  int $height The height of the cropped area, in pixels.
     * @return bool  true or false, based on success.
     */
    public function crop(Image $image, $x, $y, $width, $height);

    /**
     * Convert an image resource to grayscale.
     *
     * Note that transparent GIFs loose transparency when desaturated.
     *
     * @param  Image $image An image object. The $image->resource value will be modified by this call.
     * @return bool  true or false, based on success.
     */
    public static function desaturate(Image $image);

    /**
     * GD helper function to create an image resource from a file.
     *
     * @param  Image $image An image object. The $image->resource value will populated by this call.
     * @return bool  true or false, based on success.
     */
    public function load(Image $image);

    /**
     * GD helper to write an image resource to a destination file.
     *
     * @param  Image $image An image object.
     * @param  string $destination A string file URI or path where the image should be saved.
     * @return bool   true or false, based on success.
     */
    public function save(Image $image, $destination);

    /**
     * Get details about an image.
     *
     * @param  Image $image An image object.
     * @return bool|array false, if the file could not be found or is not an image. Otherwise, a keyed array containing information about the image:
     *   - "width": Width, in pixels.
     *   - "height": Height, in pixels.
     *   - "extension": Commonly used file extension for the image.
     *   - "mime_type": MIME type ('image/jpeg', 'image/gif', 'image/png').
     */
    public function getInfo(Image $image);
}
