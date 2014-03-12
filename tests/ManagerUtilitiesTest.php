<?php

use Onigoetz\Imagecache\Manager;

class ManagerUtilitiesTest extends ImagecacheTestCase
{
    function testURL()
    {
        $options = array('path_images' => 'img', 'path_cache' => 'cache');
        $manager = $this->getManager($options);

        $preset = 'preset';
        $file = 'file.jpg';

        $this->assertEquals(
            "{$options['path_images']}/{$options['path_cache']}/$preset/$file",
            $manager->url($preset, $file)
        );
    }

    function testImageURL()
    {
        $options = array('path_images' => 'img');
        $manager = $this->getManager($options);

        $file = 'file.jpg';

        $this->assertEquals("{$options['path_images']}/$file", $manager->imageUrl($file));
    }

    function providerPercent()
    {
        return array(
            array(500, "50%", 1000),
            array(330, "33%", 1000),
            array(200, "20%", 1000),
            array(500, 500, 1000) //directly return if it's not in percent
        );
    }

    /**
     * @dataProvider providerPercent
     */
    function testPercent($result, $percent, $current_value)
    {
        $manager = $this->getManager();

        $this->assertEquals($result, $manager->percent($percent, $current_value));
    }

    function providerKeywords()
    {
        return array(
            array(0, 'top', 1235, 1000),
            array(0, 'left', 222, 1000),
            array(100, 'right', 800, 700), //start 100px from left to keep the right 700px
            array(150, 'bottom', 950, 800),
            array(100, 'center', 800, 600), //down from 800 to 600px, will crop 100px on each side
            array(200, 200, 753, 400) //direct return if not string
        );
    }

    /**
     * @dataProvider providerKeywords
     */
    function testKeywords($result, $value, $current_pixels, $new_pixels)
    {
        $manager = new Manager(array(), $this->getMockedToolkit());

        $this->assertEquals($result, $manager->keywords($value, $current_pixels, $new_pixels));
    }
}
