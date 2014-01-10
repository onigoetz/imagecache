# Imagecache

Automatically generate images at the size you need them with presets

__works with any framework__

## How it works

let's say your images are in the `/images` web folder. you want to have thumbnails of 200x200 pixels.

You create a preset named `200x200` and configure it to scale images to 200x200 pixels

At this point, you're ready to use the module

call the url `/images/cache/200x200/your_image.jpg` and on the web server it will:

- check for the existence of `/images/cache/200x200/your_image.jpg`
  - if it exists:
    - return it
  - if not:
    - take the image `/images/your_image.jpg`
    - apply the `200x200` presets to it
    - write it to disk at `/images/cache/200x200/your_image.jpg`
    - return it to the browser
    
You've probably guessed it, the URL is constructed as follows :

`/<image folder>/<cache folder>/<preset name>/<file name>`

## Prerequisites
For it to work you need

- PHP 5.3
- Clean url's with apache url_rewrite or nginx rewrites
- 

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

to document

## Todo

- do image manipulation with Imagine and allow for greater flexibility in used libraries
- write documentation for other frameworks
- test with laravel
- test with slim framework

