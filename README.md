# Imagecache
[![Latest Version](https://img.shields.io/github/release/onigoetz/imagecache.svg?style=flat-square)](https://github.com/onigoetz/imagecache/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](https://github.com/onigoetz/imagecache/blob/master/LICENSE.md)
[![Build Status](https://img.shields.io/travis/onigoetz/imagecache/master.svg?style=flat-square)](https://travis-ci.org/onigoetz/imagecache)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/onigoetz/imagecache.svg?style=flat-square)](https://scrutinizer-ci.com/g/onigoetz/imagecache/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/onigoetz/imagecache.svg?style=flat-square)](https://scrutinizer-ci.com/g/onigoetz/imagecache)
[![Total Downloads](https://img.shields.io/packagist/dt/onigoetz/imagecache.svg?style=flat-square)](https://packagist.org/packages/onigoetz/imagecache)

Automatically generate images at the size you need them with presets

__Works with any framework__

## How it works

Provided your images folder is `images`, your cache folder is `cache` and you have a preset called `200x200`

When you call the url `/images/cache/200x200/image.jpg` and the file doesn't exist it will automatically take the file `images/image.jpg`, apply the preset to it, return it to the client and save it at the request's path to serve it from the webserver the next time.

Here is the folder structure:
```
images
├── image.jpg          // Original image
└── cache
    └── 200x200
        └── image.jpg  // Generated image using the `200x200` preset
```

An url to a cached image is built as follows :

`/<image folder>/<cache folder>/<preset name>/<file name>`

Image files can be in sub-folders, for example  `images/avatars/me.jpg` will have this url with a `40x40` preset : `images/cache/40x40/avatars/me.jpg`

## Prerequisites
For it to work you need

- PHP 5.5
- Clean urls with apache url_rewrite or nginx rewrites

## Installation

- [Laravel 5](http://github.com/onigoetz/imagecache/tree/master/docs/laravel5.md)
- [Slim Framework 3](http://github.com/onigoetz/imagecache/tree/master/docs/slim3.md)
- [Raw PHP](http://github.com/onigoetz/imagecache/tree/master/docs/raw.md)

## Preset configuration

The most important part of the module, the presets.

They're made of a a key with an array of actions to apply.

The key is the name of the preset you will use in the URL.

>
> My recommendation is to put the size of the final image in the preset name,
> this allows for more reusability in your presets.
> Because if you create a rule named for example "thumbnails" and that your
> layout changes the sizes of your thumbnails but only in some places,
> you'll soon end up with a mess with your preset names.
>

__Preset structure__

```php
'name' => [
    action,
    action ...
]
```

__Action structure__

```php
['action' => 'action_name', ... options ...]
```

[Complete list of actions and options](http://github.com/onigoetz/imagecache/tree/master/docs/actions.md)

### Example

```php
'presets' => [
    '40X40' => [   // Exact size
        ['action' => 'scale_and_crop', 'width' => 40, 'height' => 40]
    ],
    'X85' => [     // Fixed height
        ['action' => 'scale', 'height' => 85]
    ],
    '60X200' => [  // Scale to fit inside
        ['action' => 'scale', 'height' => 200, 'width' => 60]
    ],
]
```

## Retina Images

This package also helps to generate image for retina displays. there are two ways for this.

With plugins like [retina.js](http://retinajs.com/) the page will automatically try urls with __@2x__ just before the extension.

When a normal image's url is `/images/cache/200x200/koala.jpg` it will resolve to the original file `koala.jpg`.

But if you call the url `/images/cache/200x200/koala@2x.jpg` it will also resolve to the file `koala.jpg`.

This will take the `200x200` preset and double all it's values, so if you crop your images to 200x200 pixels, it will now be a 400x400 pixels image.

And it will save it back to `images/cache/200x200/koala@2x.jpg` so your apache/nginx will be able to serve it on next visit.
