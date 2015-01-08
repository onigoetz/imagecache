<?php

use Mockery as m;
use Onigoetz\Imagecache\Image;
use org\bovigo\vfs\vfsStream;

class ImageIntegrationTest extends ImagecacheTestCase
{
    function setAccessible($methodName)
    {
        $method = new ReflectionMethod('Onigoetz\Imagecache\Manager', $methodName);
        $method->setAccessible(true);

        return $method;
    }

    function providerImageGenerator()
    {
        return array(
            array(
                array(
                    array('action' => 'scale_and_crop', 'width' => 40, 'height' => 30),
                ),
                'scale_and_crop-40-30.png'
            ),
            array(
                array(
                    array('action' => 'scale_and_crop', 'width' => 60, 'height' => 60),
                ),
                'scale_and_crop-60-60.png'
            ),
            array(
                array(
                    array('action' => 'scale', 'width' => 40),
                ),
                'scale-40-__.png'
            ),
            array(
                array(
                    array('action' => 'scale', 'width' => 600),
                ),
                'scale-600.png'
            ),
            array(
                array(
                    array('action' => 'scale', 'width' => 600, 'upscale' => true),
                ),
                'scale-600-upscaled.png'
            ),
            array(
                array(
                    array('action' => 'scale', 'height' => 60),
                ),
                'scale-__-60.png'
            ),
            array(
                array(
                    array('action' => 'scale', 'height' => 60, 'width' => 60),
                ),
                'scale-60-60.png'
            ),
            array(
                array(
                    array('action' => 'resize', 'width' => 40, 'height' => 40),
                ),
                'resize-40-40.png'
            ),
            array(
                array(
                    array('action' => 'rotate', 'degrees' => 90),
                ),
                'rotate-90.png',
                '5.5'
            ),
            array(
                array(
                    array('action' => 'rotate', 'degrees' => 60, 'background' => '#FF0000'),
                ),
                'rotate-60-F00.png',
                '5.5'
            ),
            array(
                array(
                    array('action' => 'crop', 'width' => 40, 'height' => 30, 'xoffset' => 90, 'yoffset' => 80),
                ),
                'crop-40-30-90-80.png'
            ),
            array(
                array(
                    array('action' => 'crop', 'width' => 40, 'height' => 25, 'xoffset' => 90, 'yoffset' => 80),
                ),
                'crop-40-25-90-80.png'
            ),
            array(
                array(
                    array('action' => 'crop', 'width' => 50, 'height' => 30, 'xoffset' => 120, 'yoffset' => 100),
                ),
                'crop-50-30-120-100.png'
            ),
            array(
                array(
                    array('action' => 'resize', 'width' => 80, 'height' => 80), //resize for processing speed
                    array('action' => 'desaturate'),
                ),
                'desaturate.png'
            )
        );
    }

    /**
     * @dataProvider providerImageGenerator
     */
    function testGenerateImage($preset, $generated, $requires = null)
    {
        if ($requires && version_compare(PHP_VERSION, $requires, '<')) {
            $this->markTestSkipped('PHP %s (or later) is required.', $requires);
        }

        $manager = $this->getManager();
        $original_file = vfsStream::url('root/images') . '/' . $this->getDummyImageName();
        $final_file = vfsStream::url('root/images') . '/' . $generated;
        $final_file_compared = __DIR__ . '/Fixtures/result/' . $generated;

        $image = new Image($original_file);

        //uncomment and the images will be created on disk
        //$this->assertTrue($this->setAccessible('buildImage')->invoke($manager, $preset, $image, $final_file_compared));

        $this->assertTrue($this->setAccessible('buildImage')->invoke($manager, $preset, $image, $final_file));

        //generated images must be at least 95% identical
        $this->assertGreaterThan(95, ImageCompare::compare($final_file, $final_file_compared));
    }

    function providerFailedImageGenerator()
    {
        return [
            [
                [
                    ['action' => 'scale'],
                ],
                'You should at least provide width or height'
            ],
        ];
    }

    /**
     * @dataProvider providerFailedImageGenerator
     * @expectedException \LogicException
     */
    function testFailGenerateImage($preset)
    {
        $manager = $this->getManager();
        $original_file = vfsStream::url('root/images') . '/' . $this->getDummyImageName();
        $final_file = vfsStream::url('root/images') . '/willFailAnyway.png';

        $image = new Image($original_file);

        $this->setAccessible('buildImage')->invoke($manager, $preset, $image, $final_file);
    }
}
