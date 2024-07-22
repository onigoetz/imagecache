<?php namespace Onigoetz\ImagecacheTests;

use ReflectionMethod;
use Jenssegers\ImageHash\ImageHash;
use Jenssegers\ImageHash\Implementations\DifferenceHash;
use Onigoetz\Imagecache\Image;
use Onigoetz\ImagecacheUtils\ImagecacheTestCase;
use org\bovigo\vfs\vfsStream;

class ImageIntegrationTest extends ImagecacheTestCase
{
    public function setAccessible($methodName)
    {
        $method = new ReflectionMethod('Onigoetz\Imagecache\Manager', $methodName);
        $method->setAccessible(true);

        return $method;
    }

    public static function providerImageGenerator()
    {
        return [
            [
                [
                    ['action' => 'scale_and_crop', 'width' => 40, 'height' => 30],
                ],
                'scale_and_crop-40-30.png',
            ],
            [
                [
                    ['action' => 'scale_and_crop', 'width' => 60, 'height' => 60],
                ],
                'scale_and_crop-60-60.png',
            ],
            [
                [
                    ['action' => 'scale', 'width' => 40],
                ],
                'scale-40-__.png',
            ],
            [
                [
                    ['action' => 'scale', 'width' => 600],
                ],
                'scale-600.png',
            ],
            [
                [
                    ['action' => 'scale', 'height' => 60],
                ],
                'scale-__-60.png',
            ],
            [
                [
                    ['action' => 'scale', 'height' => 60, 'width' => 60],
                ],
                'scale-60-60.png',
            ],
            [
                [
                    ['action' => 'resize', 'width' => 40, 'height' => 40],
                ],
                'resize-40-40.png',
            ],
            [
                [
                    ['action' => 'rotate', 'degrees' => 90],
                ],
                'rotate-90.png',
                '5.5',
            ],
            [
                [
                    ['action' => 'rotate', 'degrees' => 60, 'background' => '#FF0000'],
                ],
                'rotate-60-F00.png',
                '5.5',
            ],
            [
                [
                    ['action' => 'crop', 'width' => 40, 'height' => 30, 'xoffset' => 90, 'yoffset' => 80],
                ],
                'crop-40-30-90-80.png',
            ],
            [
                [
                    ['action' => 'crop', 'width' => 40, 'height' => 25, 'xoffset' => 90, 'yoffset' => 80],
                ],
                'crop-40-25-90-80.png',
            ],
            [
                [
                    ['action' => 'crop', 'width' => 50, 'height' => 30, 'xoffset' => 120, 'yoffset' => 100],
                ],
                'crop-50-30-120-100.png',
            ],
            [
                [
                    ['action' => 'resize', 'width' => 80, 'height' => 80], //resize for processing speed
                    ['action' => 'desaturate'],
                ],
                'desaturate.png',
            ],
        ];
    }

    /**
     * @dataProvider providerImageGenerator
     */
    public function testGenerateImage($preset, $generated, $requires = null)
    {
        if ($requires && version_compare(PHP_VERSION, $requires, '<')) {
            $this->markTestSkipped('PHP %s (or later) is required.', $requires);
        }

        $manager = $this->getManager();
        $original_file = vfsStream::url('root') . '/' . $this->getDummyImageName();
        $final_file = vfsStream::url('root') . '/' . $generated;
        $final_file_compared = __DIR__ . '/../test-utils/Fixtures/result/' . $generated;

        $image = new Image($original_file);

        //uncomment and the images will be created on disk
        //$this->assertInstanceOf('\Onigoetz\Imagecache\Image', $this->setAccessible('buildImage')->invoke($manager, $preset, $image, $final_file_compared));

        $this->assertInstanceOf(
            \Onigoetz\Imagecache\Image::class,
            $this->setAccessible('buildImage')->invoke($manager, $preset, $image, $final_file)
        );

        $hasher = new ImageHash(new DifferenceHash);

        $generated = $hasher->hash($final_file);
        $expected = $hasher->hash($final_file_compared);

        $this->assertLessThan(4, $expected->distance($generated));
    }

    public static function providerFailedImageGenerator()
    {
        return [
            [
                [
                    ['action' => 'scale'],
                ],
                'You should at least provide width or height',
            ],
        ];
    }

    /**
     * @dataProvider providerFailedImageGenerator
     */
    public function testFailGenerateImage($preset)
    {
        $this->expectException(\LogicException::class);
        $manager = $this->getManager();
        $original_file = vfsStream::url('root') . '/' . $this->getDummyImageName();
        $final_file = vfsStream::url('root') . '/willFailAnyway.png';

        $image = new Image($original_file);

        $this->setAccessible('buildImage')->invoke($manager, $preset, $image, $final_file);
    }
}
