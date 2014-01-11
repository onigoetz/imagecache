<?php

/**
 * Contains imagecache actions
 */

namespace Onigoetz\Imagecache;

/**
 * Class Actions
 * @package Imagecache
 */
class Actions
{
    /**
     * OPTIONS :
     * width : Pixels
     * height : Pixels
     *
     * @param  Image $image
     * @param  array $data
     * @return bool
     */
    public static function resize(&$image, $data)
    {
        $data += array('width' => null, 'height' => null);

        if (!$image->resize($data['width'], $data['height'])) {
            return false;
        }

        return true;
    }

    /**
     * OPTIONS :
     * width : Pixels or percentage (not required)
     * height : Pixels or percentage (not required)
     * upscale : boolean
     *
     * at least one size field is required
     *
     * @param  Image $image
     * @param  array $data
     * @return bool
     */
    public static function scale(&$image, $data)
    {
        $data += array('upscale' => false);

        // Set impossibly large values if the width and height aren't set.
        $data['width'] = isset($data['width']) ? $data['width'] : 9999999;
        $data['height'] = isset($data['height']) ? $data['height'] : 9999999;
        if (!$image->scale($data['width'], $data['height'], $data['upscale'])) {
            return false;
        }

        return true;
    }

    /**
     *
     * OPTIONS :
     * width : Pixels or percentage (not required)
     * height : Pixels or percentage (not required)
     *
     * @param  Image $image
     * @param  array $data
     * @return bool
     */
    public static function scale_and_crop(&$image, $data)
    {
        $data += array('width' => null, 'height' => null);

        if (!$image->scale_and_crop($data['width'], $data['height'])) {
            return false;
        }

        return true;
    }

    /**
     * Crop an image
     *
     * OPTIONS :
     * width : Pixels or percentage (not required)
     * height : Pixels or percentage (not required)
     * xoffset : Pixels, percentage or keyword (left, center, right, top, bottom)
     * yoffset : Pixels, percentage or keyword (left, center, right, top, bottom)
     *
     * @param  Image $image
     * @param  array $data
     * @return bool
     */
    public static function crop(&$image, $data)
    {
        $data += array('width' => null, 'height' => null, 'xoffset' => null, 'yoffset' => null);

        if (!$image->crop($data['xoffset'], $data['yoffset'], $data['width'], $data['height'])) {
            return false;
        }

        return true;
    }

    /**
     * Desaturate an image
     *
     * @param  Image $image
     * @return bool
     */
    public static function desaturate(&$image)
    {
        if (!$image->desaturate()) {
            return false;
        }

        return true;
    }

    /**
     * Crop an image
     *
     * OPTIONS :
     * degrees : The number of degrees the image should be rotated. Positive numbers are clockwise, negative are counter-clockwise.
     * random : Randomize the rotation angle for each image. The angle specified above is used as a maximum.
     * bgcolor : The background color to use for exposed areas of the image. Use web-style hex colors (#FFFFFF for white, #000000 for black). An empty value will cause images that support transparency to have transparent backgrounds, otherwise it will be white.
     *
     * @param  Image $image
     * @param  array $data
     * @return bool
     */
    public static function rotate(&$image, $data)
    {
        // Merge in default values.
        $data += array(
            'degrees' => '0',
            'random' => false,
            'bgcolor' => '',
        );

        // Set sane default values.
        if (strlen(trim($data['bgcolor']))) {
            $data['bgcolor'] = hexdec(str_replace('#', '', $data['bgcolor']));
        } else {
            $data['bgcolor'] = null;
        }

        if ($data['random']) {
            $degrees = abs((float)$data['degrees']);
            $data['degrees'] = rand(-1 * $degrees, $degrees);
        }

        if (!$image->rotate($data['degrees'], $data['bgcolor'])) {
            return false;
        }

        return true;
    }
}
