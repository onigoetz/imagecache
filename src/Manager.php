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
class Manager
{
	public function __construct($options, $toolkit)
	{		
		$this->options = $options;
		$this->toolkit = $toolkit;
	}
	
	public function url($path, $preset)
	{
		preg_match('|\/?' . preg_quote($this->options['path_images']) . '\/(.*)', $path, $matches);
		
		return $this->preset_url($preset, $matches[1]);
	}
	
	protected function preset_url($preset, $file)
	{
		return "{$this->options['path_images']}/{$this->options['path_cache']}/$preset/$file";
	}
	
	protected function image_url($file)
	{
		return "{$this->options['path_images']}/$file";
	}
	
	public function get_preset_actions($preset, $file)
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

            if (array_key_exists($preset_key, $presets)) {
                $preset = $presets[$preset_key];
            } else {
                foreach ($preset as &$action) {
                    if (array_key_exists('width', $action)) {
                        $action['width'] = $action['width']*2;
                    }
                    if (array_key_exists('height', $action)) {
                        $action['height'] = $action['height']*2;
                    }
                }
            }
        }
		
		return $preset;
	}
	
	public function get_toolkit()
	{
		return $gd;
	}
	
	public function handle_request($preset_key, $file)
	{
		$preset = $this->get_preset_actions($preset_key, $file);

        $final_file = $this->options['path_images_root'] . '/' . $this->preset_url($preset_key, $file);
        $original_file = $this->options['path_images_root'] . '/' . $this->image_url($file);

        if (!is_file($original_file)) {
			throw new Exceptions\NotFoundException('File not found');
        }

        //create the folder path (and chmod it)
        $folder_path = explode('/', str_replace($image_path.'/', '', dirname($final_file)));
        foreach ($folder_path as $element) {
            $image_path.= '/' . $element;
            if (!is_dir($image_path)) {
                mkdir($image_path, 0755, true);
                chmod($image_path, 0755);
            }
        }

        if (file_exists($final_file) || $manager->build_image($preset, $original_file, $final_file)) {          
			return $final_file;
        }
		
		return false;
	}

    /**
     * Create a new image based on an image preset.
     *
     * @param  array  $actions An image preset array.
     * @param  string $src     Path of the source file.
     * @param  string $dst     Path of the destination file.
     * @return bool   true if an image derivative is generated, false if no image derivative is generated. NULL if the derivative is being generated.
     */
    public function build_image($actions, $src, $dst)
    {
        if (!$image = new Image($src, $this->toolkit)) {
            return false;
        }

        foreach ($actions as $action) {
            // Make sure the width and height are computed first so they can be used
            // in relative x/yoffsets like 'center' or 'bottom'.
            if (isset($action['width'])) {
                $action['width'] = $this->percent_filter($action['width'], $image->info['width']);
            }
            if (isset($action['height'])) {
                $action['height'] = $this->percent_filter($action['height'], $image->info['height']);
            }

            if (isset($action['xoffset'])) {
                $action['xoffset'] = $this->keyword_filter($action['xoffset'], $image->info['width'], $action['width']);
            }
            if (isset($action['yoffset'])) {
                $action['yoffset'] = $this->keyword_filter($action['yoffset'], $image->info['height'], $action['height']);
            }

            if (!method_exists('\\Imagecache\\Actions', $action['action'])) {
                return false;
            }

            if (!Actions::$action['action']($image, $action)) {
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
     * @param  int    $current_pixels
     * @return mixed
     */
    private function percent_filter($value, $current_pixels)
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
    private function keyword_filter($value, $current_pixels, $new_pixels)
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