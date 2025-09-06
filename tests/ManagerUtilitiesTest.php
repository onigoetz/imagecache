<?php namespace Onigoetz\ImagecacheTests;

use Onigoetz\Imagecache\Manager;
use Onigoetz\ImagecacheUtils\ImagecacheTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ManagerUtilitiesTest extends ImagecacheTestCase
{
    public function testURL()
    {
        $options = ['path_web' => 'img', 'path_cache' => 'cache'];
        $manager = $this->getManager($options);

        $preset = 'preset';
        $file = 'file.jpg';

        $this->assertEquals(
            "{$options['path_web']}/{$options['path_cache']}/$preset/$file",
            $manager->url($preset, $file)
        );
    }

    public function testImageURL()
    {
        $options = ['path_web' => 'img'];
        $manager = $this->getManager($options);

        $file = 'file.jpg';

        $this->assertEquals("{$options['path_web']}/$file", $manager->imageUrl($file));
    }

    public static function providerPercent()
    {
        return [
            [500, '50%', 1000],
            [330, '33%', 1000],
            [200, '20%', 1000],
            [500, 500, 1000], //directly return if it's not in percent
        ];
    }

    #[DataProvider('providerPercent')]
    public function testPercent($result, $percent, $current_value)
    {
        $manager = $this->getManager();

        $this->assertEquals($result, $manager->percent($percent, $current_value));
    }

    public static function providerKeywords()
    {
        return [
            [0, 'top', 1235, 1000],
            [0, 'left', 222, 1000],
            [100, 'right', 800, 700], //start 100px from left to keep the right 700px
            [150, 'bottom', 950, 800],
            [100, 'center', 800, 600], //down from 800 to 600px, will crop 100px on each side
            [200, 200, 753, 400], //direct return if not string
        ];
    }

    #[DataProvider('providerKeywords')]
    public function testKeywords($result, $value, $current_pixels, $new_pixels)
    {
        $manager = new Manager([]);

        $this->assertEquals($result, $manager->keywords($value, $current_pixels, $new_pixels));
    }
}
