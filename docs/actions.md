

## Available actions

### `resize`

Resize an image to the given dimensions (ignoring aspect ratio).

__Options__

- `width` : The target width, in pixels or percents.
- `height` : The target height, in pixels or percents.

### `scale`

Scales an image to the given width and height while maintaining aspect ratio.

The resulting image can be smaller for one or both target dimensions.

__Options__

- `width` : The target width, in pixels or percents. This value is omitted then the scaling will based only on the height value.
- `height` : The target height, in pixels or percents. This value is omitted then the scaling will based only on the width value.
- `upscale` : Boolean indicating that files smaller than the dimensions will be scaled up. This generally results in a low quality image. (Defaults to `false`)

### `scale_and_crop`

Scales an image to the exact width and height given.

This function achieves the target aspect ratio by cropping the original image
equally on both sides, or equally on the top and bottom. This function is
useful to create uniform sized avatars from larger images.

The resulting image always has the exact target dimensions.

__Options__

- `width` : The target width, in pixels or percents.
- `height` : The target height, in pixels or percents.


### `crop`

Crop an image to the rectangle specified by the given rectangle.

__Options__

- `width` : The target width, in pixels or percents.
- `height` : The target height, in pixels or percents.
- `xoffset` : The top left coordinate, in pixels or keyword (top, left, bottom, right, center) of the crop area (x axis value), 
- `yoffset` : The top left coordinate, in pixels or keyword (top, left, bottom, right, center) of the crop area (y axis value).


### `desaturate`

Convert an image to grayscale.

__Options__

no options

### `rotate`

Rotate an image by the given number of degrees.

__Options__

- `degrees` : The number of degrees the image should be rotated. Positive numbers are clockwise, negative are counter-clockwise.
- `random` : Randomize the rotation angle for each image. The angle specified above is used as a maximum.
- `bgcolor` : The background color to use for exposed areas of the image. Use web-style hex colors (#FFFFFF for white, #000000 for black). An empty value will cause images that support transparency to have transparent backgrounds, otherwise it will be white.