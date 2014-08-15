<?php

/**
 * Image Manager
 */

namespace Onigoetz\Imagecache;

use ReflectionMethod;

/**
 * Image manager
 *
 * Prepares the images for the cache
 *
 * @package Imagecache
 *
 * @author Stéphane Goetz
 */
class Manager
{
    /**
     * @var array Contains configurations and presets
     */
    protected $options;

    /**
     * @var string Image manipulator to use (GD for the moment)
     */
    protected $toolkit;

    /**
     * @var MethodCaller
     */
    protected $methodCaller;

    public function __construct($options, $toolkit)
    {
        $this->options = $options + array('path_images' => 'images', 'path_cache' => 'cache');
        $this->toolkit = $toolkit;
    }

    /**
     * @return MethodCaller
     */
    public function getMethodCaller()
    {
        if (!$this->methodCaller) {
            $this->methodCaller = new MethodCaller();
        }

        return $this->methodCaller;

    }

    /**
     * @param MethodCaller $methodCaller
     */
    public function setMethodCaller(MethodCaller $methodCaller)
    {
        $this->methodCaller = $methodCaller;
    }

    public function url($preset, $file)
    {
        return "{$this->options['path_images']}/{$this->options['path_cache']}/$preset/$file";
    }

    public function imageUrl($file)
    {
        return "{$this->options['path_images']}/$file";
    }

    protected function getPresetActions($preset_key, $file)
    {
        //Is it a valid preset
        if (!array_key_exists($preset_key, $this->options['presets'])) {
            throw new Exceptions\InvalidPresetException('invalid preset');
        }

        $preset = $this->options['presets'][$preset_key];

        //Handle retina images
        if (strpos($file, '@2x') !== false) {
            $file = str_replace('@2x', '', $file);
            $preset_key = $preset_key . '@2x';

            if (array_key_exists($preset_key, $this->options['presets'])) {
                $preset = $this->options['presets'][$preset_key];
            } else {
                foreach ($preset as &$action) {
                    $action = $this->generateRetinaAction($action);
                }
            }
        }

        return array($preset, $preset_key, $file);
    }

    protected function generateRetinaAction($action)
    {
        foreach (array('width', 'height', 'xoffset', 'yoffset') as $option) {
            if (array_key_exists($option, $action) && is_numeric($action[$option])) {
                $action[$option] *= 2;
            }
        }

        return $action;
    }

    public function handleRequest($preset_key, $file)
    {
        //do it at the beginning for early validation
        list($preset, $preset_key, $file) = $this->getPresetActions($preset_key, $file);

        $original_file = $this->options['path_images_root'] . '/' . $this->imageUrl($file);
        if (!is_file($original_file)) {
            throw new Exceptions\NotFoundException('File not found');
        }

        $final_file = $this->url($preset_key, $file);

        //create the folder path (and chmod it)
        $directory = dirname($final_file);
        if (!is_dir($directory)) {
            $folder_path = explode('/', $directory);
            $image_path = $this->options['path_images_root'];
            foreach ($folder_path as $element) {
                $image_path .= '/' . $element;
                if (!is_dir($image_path)) {
                    mkdir($image_path, 0755, true);
                    chmod($image_path, 0755);
                }
            }
        }

        $final_file = $this->options['path_images_root'] . '/' . $final_file;

        if (file_exists($final_file)) {
            return $final_file;
        }

        if (!$image = $this->loadImage($original_file)) {
            return false;
        }

        if ($this->buildImage($preset, $image, $final_file)) {
            return $final_file;
        }

        return false;
    }

    protected function loadImage($src)
    {
        return new Image($src, $this->toolkit);
    }

    /**
     * Create a new image based on an image preset.
     *
     * @param  array $actions An image preset array.
     * @param  Image $image Path of the source file.
     * @param  string $dst Path of the destination file.
     * @return bool   true if an image derivative is generated, false if no image derivative is generated. NULL if the derivative is being generated.
     */
    protected function buildImage($actions, Image $image, $dst)
    {
        foreach ($actions as $action) {
            // Make sure the width and height are computed first so they can be used
            // in relative x/yoffsets like 'center' or 'bottom'.
            if (isset($action['width'])) {
                $action['width'] = $this->percent($action['width'], $image->getWidth());
            }

            if (isset($action['height'])) {
                $action['height'] = $this->percent($action['height'], $image->getHeight());
            }

            if (isset($action['xoffset'])) {
                $action['xoffset'] = $this->keywords($action['xoffset'], $image->getWidth(), $action['width']);
            }

            if (isset($action['yoffset'])) {
                $action['yoffset'] = $this->keywords($action['yoffset'], $image->getHeight(), $action['height']);
            }

            $method = $action['action'];

            if (!$this->getMethodCaller()->call($image, $method, $action)) {
                return false;
            }
        }

        if (!$image->save($dst)) {
            return false;
        }

        return true;
    }

    /**
     * Accept a percentage and return it in pixels.
     *
     * @param  string $value
     * @param  int $current_pixels
     * @return mixed
     */
    public function percent($value, $current_pixels)
    {
        if (strpos($value, '%') !== false) {
            $value = str_replace('%', '', $value) * 0.01 * $current_pixels;
        }

        return $value;
    }

    /**
     * Accept a keyword (center, top, left, etc) and return it as an offset in pixels.
     *
     * @param $value
     * @param $current_pixels
     * @param $new_pixels
     * @return float|int
     */
    public function keywords($value, $current_pixels, $new_pixels)
    {
        switch ($value) {
            case 'top':
            case 'left':
                $value = 0;
                break;
            case 'bottom':
            case 'right':
                $value = $current_pixels - $new_pixels;
                break;
            case 'center':
                $value = $current_pixels / 2 - $new_pixels / 2;
                break;
        }

        return $value;
    }
}
