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
     * @var MethodCaller
     */
    protected $methodCaller;

    /**
     * @var string
     */
    protected $retinaRegex = '/(.*)@2x\\.(jpe?g|png|webp|gif)/';

    public function __construct($options)
    {
        $this->options = $options + ['path_web' => 'images', 'path_cache' => 'cache'];
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

    public function localUrl($preset, $file) {
        return "{$this->options['path_cache']}/$preset/$file";
    }

    public function url($preset, $file)
    {
        return "{$this->options['path_web']}/{$this->options['path_cache']}/$preset/$file";
    }

    public function imageUrl($file)
    {
        return "{$this->options['path_web']}/$file";
    }

    public function isRetina($file)
    {
        return !!preg_match($this->retinaRegex, $file);
    }

    public function getOriginalFilename($file) {
        $matched = preg_match($this->retinaRegex, $file, $matches);

        return ($matched)? $matches[1] . '.' . $matches[2] : $file;
    }

    protected function getPresetActions($preset_key, $file)
    {
        // Is it a valid preset
        if (!array_key_exists($preset_key, $this->options['presets'])) {
            throw new Exceptions\InvalidPresetException('invalid preset');
        }

        $preset = $this->options['presets'][$preset_key];

        if (!$this->isRetina($file)) {
            return $preset;
        }

        // Handle retina images

        $preset_key = "$preset_key@2x";

        if (array_key_exists($preset_key, $this->options['presets'])) {
            return $this->options['presets'][$preset_key];
        }

        foreach ($preset as &$action) {
            $action = $this->generateRetinaAction($action);
        }

        return $preset;
    }

    protected function generateRetinaAction($action)
    {
        foreach (['width', 'height', 'xoffset', 'yoffset'] as $option) {
            if (array_key_exists($option, $action) && is_numeric($action[$option])) {
                $action[$option] *= 2;
            }
        }

        return $action;
    }

    /**
     * Take a preset and a file and return a transformed image
     *
     * @param $preset_key string
     * @param $file string
     * @throws Exceptions\InvalidPresetException
     * @throws Exceptions\NotFoundException
     * @throws \RuntimeException
     * @return string
     */
    public function handleRequest($preset_key, $file)
    {
        //do it at the beginning for early validation
        $preset = $this->getPresetActions($preset_key, $file);

        $source_file =  $this->getOriginalFilename($file);

        $original_file = $this->options['path_local'] . '/' . $source_file;
        if (!is_file($original_file)) {
            throw new Exceptions\NotFoundException('File not found');
        }

        $final_file = $this->localUrl($preset_key, $file);

        $this->verifyDirectoryExistence($this->options['path_local'], dirname($final_file));

        $final_file = $this->options['path_local'] . '/' . $final_file;

        if (file_exists($final_file)) {
            return $final_file;
        }

        $image = $this->loadImage($original_file);

        return $this->buildImage($preset, $image, $final_file)->source;
    }

    /**
     * Create the folder containing the cached images if it doesn't exist
     *
     * @param $base
     * @param $cacheDir
     */
    protected function verifyDirectoryExistence($base, $cacheDir)
    {
        if (is_dir("$base/$cacheDir")) {
            return;
        }

        $folder_path = explode('/', $cacheDir);
        foreach ($folder_path as $element) {
            $base .= '/' . $element;
            if (!is_dir($base)) {
                mkdir($base, 0755, true);
                chmod($base, 0755);
            }
        }
    }

    protected function loadImage($src)
    {
        return new Image($src);
    }

    /**
     * Create a new image based on an image preset.
     *
     * @param array $actions An image preset array.
     * @param Image $image Path of the source file.
     * @param string $dst Path of the destination file.
     * @throws \RuntimeException
     * @return Image
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

            $this->getMethodCaller()->call($image, $action['action'], $action);
        }

        return $image->save($dst);
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
