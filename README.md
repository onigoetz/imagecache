# Imagecache
[![Build Status](https://travis-ci.org/onigoetz/imagecache.png?branch=master)](http://travis-ci.org/onigoetz/imagecache) [![Coverage Status](https://coveralls.io/repos/onigoetz/imagecache/badge.png?branch=master)](https://coveralls.io/r/onigoetz/imagecache?branch=master)

Automatically generate images at the size you need them with presets

__works with any framework__

## How it works

let's say your images are in the `/images` web folder. you want to have thumbnails of 200x200 pixels.

You create a preset named `200x200` and configure it to scale images to 200x200 pixels

At this point, you're ready to use the module

call the url `/imagecache/200x200/your_image.jpg` and on the web server it will:

- check for the existence of `/imagecache/200x200/your_image.jpg`
  - if it exists:
    - return it
  - if not:
    - take the image `/images/your_image.jpg`
    - apply the `200x200` presets to it
    - write it to disk at `/imagecache/200x200/your_image.jpg`
    - return it to the browser

You've probably guessed it, the URL is constructed as follows :

`/<cache folder>/<preset name>/<file name>`

## Demo

```config
    'images_url' => 'imagecache', // imagecache/:preset/:file
    'path_images_root' => ROOT. '/storage/uploads',
    'cache_path' => ROOT. '/storage/cache',
    'presets' => array(
        '200x200' => array( //exact size
            array('action' => 'scale_and_crop', 'width' => 200, 'height' => 200)
        ),
        'x85' => array( //fixed height
            array('action' => 'scale', 'width' => 85)
        ),
    )
```

  - url: http://localhost/app/imagecache/200x200/2014/test.jpg
  - cache patch:  /User/asins/storage/cache
  - origin patch: /User/asins/storage/uploads

set cache folder chmod 755.

## Prerequisites
For it to work you need

- PHP 5.3
- Clean urls with apache url_rewrite or nginx rewrites

## Installation

- [Raw PHP](http://github.com/onigoetz/imagecache/tree/master/docs/raw.md)
- [Laravel Framework](http://github.com/onigoetz/imagecache/tree/master/docs/laravel.md)
- [Slim Framework](http://github.com/onigoetz/imagecache/tree/master/docs/slim.md)

## Preset configuration

The most important part of the module, the presets.

They're made of a a key with an array of "actions" to apply

You can put any key you want as long as it works in a URL.

However a recommendation is to put the size of the final image in the preset name,
this allows for much more reusability in your presets. because if you create a rule named "thumbnails"
and that your layout changes the sizes of your thumbnails but only in some places, you'll soon end in a mess with the preset names

__Preset structure__

	'name' => array(
    	action,
    	action ...
	)

__Action structure__

	array('action' => 'action_name', ... options ...)

[Complete list of actions and options](http://github.com/onigoetz/imagecache/tree/master/docs/actions.md)

### Example

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

## Retina Images

This package also helps to generate image for retina displays. there are two ways for this.

with plugins like [retina.js](http://retinajs.com/) the page will automatically try urls with __@2x__ at the end.

so when a normal image's url is `/images/cache/200x200/koala.jpg` it will resolve to : `preset = 200x200` and `file = koala.jpg`.

But if you call the url `/images/cache/200x200/koala@2x.jpg` it will resolve to : `preset = 200x200@2x` and `file = koala.jpg`.

Now the interesting thing happens : if the preset exists, it is applied. If it doesn't, it will take the `200x200` preset and double all it's values.

so if in the preset `200x200` you crop your images to 200x200 pixels, it will now be a 400x400 pixels image.

## Todo

- add some unit tests
- do image manipulation with Imagine and allow for greater flexibility in manipulations
- write documentation for other frameworks


