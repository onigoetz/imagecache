<?php

/**
 * GD2 toolkit for image manipulation within Drupal.
 */

namespace Onigoetz\Imagecache\Imagekit;

use Onigoetz\Imagecache\Image;

/**
 * Gd implementation of imagecache
 *
 * @package Imagecache\Imagekit
 */
class Gd implements Toolkit
{
    /**
     * {@inheritdoc}
     */
    public function resize(Image $image, $width, $height)
    {
        $res = $this->createTmp($image, $width, $height);

        if (!imagecopyresampled($res, $image->resource, 0, 0, 0, 0, $width, $height, $image->getWidth(), $image->getHeight())) {
            return false;
        }

        imagedestroy($image->resource);
        // Update image object.
        $image->resource = $res;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function rotate(Image $image, $degrees, $background = null)
    {
        // PHP installations using non-bundled GD do not have imagerotate.
        if (!function_exists('imagerotate')) {
            throw new \Exception("The image $image->source could not be rotated because the imagerotate() function is not available in this PHP installation.");
        }

        // Convert the hexadecimal background value to a color index value.
        if (isset($background)) {
            $rgb = array();
            for ($i = 16; $i >= 0; $i -= 8) {
                $rgb[] = (($background >> $i) & 0xFF);
            }
            $background = imagecolorallocatealpha($image->resource, $rgb[0], $rgb[1], $rgb[2], 0);
        } else {
            // Set the background color as transparent if $background is NULL.

            // Get the current transparent color.
            $background = imagecolortransparent($image->resource);

            // If no transparent colors, use white.
            if ($background == 0) {
                $background = imagecolorallocatealpha($image->resource, 255, 255, 255, 0);
            }
        }

        // Images are assigned a new color palette when rotating, removing any
        // transparency flags. For GIF images, keep a record of the transparent color.
        if ($image->getExtension() == 'gif') {
            $transparent_index = imagecolortransparent($image->resource);
            if ($transparent_index != 0) {
                $transparent_gif_color = imagecolorsforindex($image->resource, $transparent_index);
            }
        }

        $image->resource = imagerotate($image->resource, 360 - $degrees, $background);

        // GIFs need to reassign the transparent color after performing the rotate.
        if (isset($transparent_gif_color)) {
            $background = imagecolorexactalpha($image->resource, $transparent_gif_color['red'], $transparent_gif_color['green'], $transparent_gif_color['blue'], $transparent_gif_color['alpha']);
            imagecolortransparent($image->resource, $background);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function crop(Image $image, $x, $y, $width, $height)
    {
        $res = $this->createTmp($image, $width, $height);

        if (!imagecopyresampled($res, $image->resource, 0, 0, $x, $y, $width, $height, $width, $height)) {
            return false;
        }

        // Destroy the original image and return the modified image.
        imagedestroy($image->resource);
        $image->resource = $res;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public static function desaturate(Image $image)
    {
        // PHP installations using non-bundled GD do not have imagefilter.
        if (!function_exists('imagefilter')) {
            throw new \Exception("The image $image->source could not be desaturated because the imagefilter() function is not available in this PHP installation.");
        }

        return imagefilter($image->resource, IMG_FILTER_GRAYSCALE);
    }

    /**
     * {@inheritdoc}
     */
    public function load(Image $image)
    {
        $extension = str_replace('jpg', 'jpeg', $image->getExtension());
        $function = 'imagecreatefrom' . $extension;

        return (function_exists($function) && $image->resource = $function($image->source));
    }

    /**
     * {@inheritdoc}
     */
    public function save(Image $image, $destination)
    {
        $extension = str_replace('jpg', 'jpeg', $image->getExtension());
        $function = 'image' . $extension;

        if (!function_exists($function)) {
            return false;
        }

        if ($extension == 'jpeg') {
            return $function($image->resource, $destination, 75);
        }

        // Always save PNG images with full transparency.
        if ($extension == 'png') {
            imagealphablending($image->resource, false);
            imagesavealpha($image->resource, true);
        }

        return $function($image->resource, $destination);
    }

    /**
     * Create a truecolor image preserving transparency from a provided image.
     *
     * @param  Image $image An image object.
     * @param  int $width The new width of the new image, in pixels.
     * @param  int $height The new height of the new image, in pixels.
     * @return resource A GD image handle.
     */
    protected function createTmp(Image $image, $width, $height)
    {
        $res = imagecreatetruecolor($width, $height);

        if ($image->getExtension() == 'gif') {
            // Grab transparent color index from image resource.
            $transparent = imagecolortransparent($image->resource);

            if ($transparent >= 0) {
                // The original must have a transparent color, allocate to the new image.
                $transparent_color = imagecolorsforindex($image->resource, $transparent);
                $transparent = imagecolorallocate($res, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);

                // Flood with our new transparent color.
                imagefill($res, 0, 0, $transparent);
                imagecolortransparent($res, $transparent);
            }
        } elseif ($image->getExtension() == 'png') {
            imagealphablending($res, false);
            $transparency = imagecolorallocatealpha($res, 0, 0, 0, 127);
            imagefill($res, 0, 0, $transparency);
            imagealphablending($res, true);
            imagesavealpha($res, true);
        } else {
            imagefill($res, 0, 0, imagecolorallocate($res, 255, 255, 255));
        }

        return $res;
    }

    /**
     * {@inheritdoc}
     */
    public function getInfo(Image $image)
    {
        $details = false;
        $data = getimagesize($image->source);

        if (isset($data) && is_array($data)) {
            $extensions = array('1' => 'gif', '2' => 'jpg', '3' => 'png');
            $extension = isset($extensions[$data[2]]) ? $extensions[$data[2]] : '';
            $details = array(
                'width' => $data[0],
                'height' => $data[1],
                'extension' => $extension,
                'mime_type' => $data['mime'],
            );
        }

        return $details;
    }
}
