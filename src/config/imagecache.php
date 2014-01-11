<?php

return array(
    /*
    |--------------------------------------------------------------------------
    | Web relative path to images
    |--------------------------------------------------------------------------
    |
    | Path were you store your content related images.
	|
	| If you put `images` it means your images are accessible at : `/images/your_image.jpg` (relative to your framework install)
    |
    */
    'path_images' => 'images',

    /*
    |--------------------------------------------------------------------------
    | Absolute path to images folder
    |--------------------------------------------------------------------------
    |
    | Where is the `path_images` folder located ?
	|
	| let's say your `path_images` folder is `images` and stored in `/var/www/root/images`
	|
	| Here you would put `/var/www/root`
    |
    */
    'path_images_root' => app('path.public'),

    /*
    |--------------------------------------------------------------------------
    | Cache folder
    |--------------------------------------------------------------------------
    |
    | This folder MUST be located inside the `path_images` folder
	|
	| so if you put `images` in `path_images` and `cache` in `path_cache`
	| your image would be stored at `/images/cache/<preset>/<image.jpg>`
    |
    */
    'path_cache' => 'cache',

    /*
    |--------------------------------------------------------------------------
    | Presets
    |--------------------------------------------------------------------------
    |
    | The most important part of the module, the presets.
	|
	| They're made of a a key with an array of "actions" to apply
	|
	| You can put any key you want as long as it works in a URL.
	|
	| However a recommendation is to put the size of the final image in the preset name,
	| this allows for much more reusability in your presets. because if you create a rule named "thumbnails"
	| and that your layout changes the sizes of your thumbnails but only in some places, you'll soon end in a mess with the preset names
	|
    | Preset structure :
	| 'name' => array(
	|     action,
	|     action ...
	| )
	|
	| Action structure :
	| array('action' => 'action_name', ... options ...)
	|
	| Available actions :
	| see the README for a detailed list
    |
    */
    'presets' => array(
        '40X40' => array( //exact size
            array('action' => 'scale_and_crop', 'width' => 40, 'height' => 40)
        ),
        'X85' => array( //fixed height
            array('action' => 'scale', 'height' => 85)
        ),
        '60X200' => array( //scale to fit inside
            array('action' => 'scale', 'height' => 200, 'width' => 60)
        ),
    )
);
