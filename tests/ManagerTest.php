<?php

use Mockery as m;
use Onigoetz\Imagecache\Image;
use Onigoetz\Imagecache\MethodCaller;
use org\bovigo\vfs\vfsStream;

class ManagerTest extends ImagecacheTestCase
{
    public function getDummyImageUrl()
    {
        return vfsStream::url('root') . '/' . $this->getDummyImageName();
    }

    public function setAccessible($methodName)
    {
        $method = new ReflectionMethod('Onigoetz\Imagecache\Manager', $methodName);
        $method->setAccessible(true);

        return $method;
    }

    public function testClassExists()
    {
        $this->assertTrue(class_exists('Onigoetz\Imagecache\Manager'));
    }

    public function testGetMethodCaller()
    {
        $manager = $this->getManager();

        $this->assertInstanceOf('\Onigoetz\Imagecache\MethodCaller', $manager->getMethodCaller());
    }

    public function testSetMethodCaller()
    {
        $manager = $this->getManager();

        $manager->setMethodCaller($methodCaller = new MethodCaller);

        $this->assertInstanceOf('\Onigoetz\Imagecache\MethodCaller', $manager->getMethodCaller());
        $this->assertSame($methodCaller, $manager->getMethodCaller());
    }

    /**
     * @expectedException \Onigoetz\Imagecache\Exceptions\InvalidPresetException
     */
    public function testNonExistingPreset()
    {
        $manager = $this->getManager(['presets' => []]);

        $this->setAccessible('getPresetActions')->invoke($manager, '200X', 'file.jpg');
    }

    public function testGetPreset()
    {
        $preset = [['action' => 'scale_and_crop', 'width' => 40, 'height' => 40]];

        $manager = $this->getManager(['presets' => ['200X' => $preset]]);

        $resolved_presets = $this->setAccessible('getPresetActions')->invoke($manager, '200X', 'file.jpg');

        $this->assertEquals($preset, $resolved_presets);
    }

    public function testGetRetinaPreset()
    {
        $original_preset = [['action' => 'scale_and_crop', 'width' => 40, 'height' => 40]];
        $original_preset_key = '200X';
        $original_file = 'file@2x.jpg';

        $manager = $this->getManager(['presets' => ['200X@2x' => $original_preset, '200X' => []]]);

        $resolved_preset = $this->setAccessible('getPresetActions')->invoke(
            $manager,
            $original_preset_key,
            $original_file
        );

        $this->assertEquals($original_preset, $resolved_preset);
    }

    public function providerRetinaGenerator()
    {
        return [
            [
                ['action' => 'scale_and_crop', 'width' => 40, 'height' => 40],
                ['action' => 'scale_and_crop', 'width' => 80, 'height' => 80],
            ],
            [
                ['action' => 'scale', 'width' => 40],
                ['action' => 'scale', 'width' => 80],
            ],
            [
                ['action' => 'scale', 'width' => 40],
                ['action' => 'scale', 'width' => 80],
            ],
            [
                ['action' => 'scale', 'width' => '50%'],
                ['action' => 'scale', 'width' => '50%'],
            ],
            [
                ['action' => 'crop', 'width' => '50%', 'yoffset' => 20],
                ['action' => 'crop', 'width' => '50%', 'yoffset' => 40],
            ],
            [
                ['action' => 'crop', 'width' => '20', 'yoffset' => 'top'],
                ['action' => 'crop', 'width' => '40', 'yoffset' => 'top'],
            ],
            [
                ['action' => 'crop', 'width' => '50%', 'xoffset' => 20],
                ['action' => 'crop', 'width' => '50%', 'xoffset' => 40],
            ],
            [
                ['action' => 'crop', 'width' => '20', 'xoffset' => 'left'],
                ['action' => 'crop', 'width' => '40', 'xoffset' => 'left'],
            ],
        ];
    }

    /**
     * @dataProvider providerRetinaGenerator
     */
    public function testGenerateRetinaAction($original, $generated)
    {
        $manager = $this->getManager();

        $this->assertEquals($generated, $this->setAccessible('generateRetinaAction')->invoke($manager, $original));
    }

    public function testGenerateRetinaPreset()
    {
        $original_preset = [['action' => 'scale', 'width' => 40], ['action' => 'scale', 'width' => 60]];
        $generated_preset = [
            ['action' => 'scale', 'width' => 80],
            ['action' => 'scale', 'width' => 120],
        ];
        $original_preset_key = '200X';
        $original_file = 'file@2x.jpg';

        $manager = $this->getManager(['presets' => ['200X' => $original_preset]]);

        $resolved_preset = $this->setAccessible('getPresetActions')->invoke(
            $manager,
            $original_preset_key,
            $original_file
        );

        $this->assertEquals($generated_preset, $resolved_preset);
    }

    public function testLoadImage()
    {
        $manager = $this->getManager();

        $this->assertInstanceOf(
            'Onigoetz\Imagecache\Image',
            $this->setAccessible('loadImage')->invoke($manager, 'vfs://root/' . $this->getDummyImageName())
        );
    }

    /**
     * @expectedException \Onigoetz\Imagecache\Exceptions\NotFoundException
     * @covers Onigoetz\Imagecache\Manager::handleRequest
     */
    public function testNonExistingFile()
    {
        $manager = $this->getManager(['presets' => ['200X' => []]]);

        $manager->handleRequest('200X', 'file.jpg');
    }

    /**
     * @covers Onigoetz\Imagecache\Manager::handleRequest
     */
    public function testAlreadyExists()
    {
        $preset = '200X';
        $file = $this->getDummyImageName();
        $final_file = 'vfs://root/cache/' . $preset . '/' . $file;
        $dir = dirname($final_file);

        $manager = $this->getMockedManager(['presets' => [$preset => []]]);

        //Create file
        mkdir($dir, 0755, true);
        touch($final_file);

        $manager->shouldReceive('buildImage')->never();

        $this->assertEquals($final_file, $manager->handleRequest($preset, $file));
    }

    /**
     * @covers Onigoetz\Imagecache\Manager::handleRequest
     */
    public function testHandleRequestCreateDirectory()
    {
        $preset = '200X';
        $file = $this->getDummyImageName();

        $manager = $this->getMockedManager(['presets' => [$preset => []]]);

        $this->assertNull($this->vfsRoot->getChild('cache'));

        $manager->shouldReceive('buildImage')->andReturn(new Image($this->getDummyImageUrl()));

        $manager->handleRequest($preset, $file);

        $this->assertEquals(
            0755,
            $this->vfsRoot->getChild('cache')->getChild($preset)->getPermissions()
        );
    }

    /**
     * @covers Onigoetz\Imagecache\Manager::handleRequest
     * @expectedException \RuntimeException
     */
    public function testHandleRequestCannotLoadImage()
    {
        $preset = '200X';

        $manager = $this->getMockedManager(['presets' => [$preset => []]]);
        $manager->shouldReceive('loadImage')->andThrow(new \RuntimeException('corrupt image'));

        $manager->handleRequest($preset, $this->getDummyImageName());
    }

    /**
     * @covers Onigoetz\Imagecache\Manager::handleRequest
     * @covers Onigoetz\Imagecache\Manager::verifyDirectoryExistence
     */
    public function testHandleRequestFull()
    {
        $imageFolder = $this->getImageFolder();

        $file = $this->getDummyImageName();
        $preset = '200X';
        $expected = [
            'original_file' => 'vfs://root/' . $file,
            'preset' => [],
            'final_file' => 'vfs://root/cache/' . $preset . '/' . $file,
        ];
        $expected['image'] = new Image($expected['original_file']);

        $manager = $this->getMockedManager(
            ['presets' => [$preset => $expected['preset']], 'path_local' => $imageFolder]
        );

        $manager->shouldReceive('loadImage')->with($expected['original_file'])->andReturn($expected['image']);
        $manager->shouldReceive('buildImage')
            ->once()
            ->with($expected['preset'], $expected['image'], $expected['final_file'])
            ->andReturn(new Image($expected['original_file'])); //we use the original file, so we don't have to generate one.

        $manager->handleRequest($preset, $file);

        // as the image generation is mocked
        // we need to create it now
        touch($expected['final_file']);

        // do it twice to test that the directory
        // is not created twice and that the image
        // is sent without being re-generated
        $manager->handleRequest($preset, $file);
    }

    /**
     * @covers Onigoetz\Imagecache\Manager::handleRequest
     * @expectedException \RuntimeException
     */
    public function testHandleFailedRequest()
    {
        $file = $this->getDummyImageName();
        $preset = '200X';

        $manager = $this->getMockedManager(['presets' => [$preset => []]]);

        $manager->shouldReceive('buildImage')->andThrow(new \RuntimeException('failed for a reason'));

        $manager->handleRequest($preset, $file);
    }

    public function providerBuildImage()
    {
        //the example image is 500x500

        return [
            [
                ['action' => 'scale_and_crop', 'width' => 40, 'height' => '25%'],
                ['action' => 'scale_and_crop', 'width' => 40, 'height' => 125],
            ],
            [
                ['action' => 'scale', 'width' => 40],
                ['action' => 'scale', 'width' => 40],
            ],
            [
                ['action' => 'scale', 'width' => '50%'],
                ['action' => 'scale', 'width' => 250],
            ],
            [
                ['action' => 'crop', 'height' => '50%', 'yoffset' => 20],
                ['action' => 'crop', 'height' => 250, 'yoffset' => 20],
            ],
            [
                ['action' => 'crop', 'height' => 20, 'yoffset' => 'bottom'],
                ['action' => 'crop', 'height' => 20, 'yoffset' => 480],
            ],
            [
                ['action' => 'crop', 'width' => '50%', 'xoffset' => 'center'],
                ['action' => 'crop', 'width' => '250', 'xoffset' => 125],
            ],
            [
                ['action' => 'crop', 'width' => '20', 'xoffset' => 'left'],
                ['action' => 'crop', 'width' => '20', 'xoffset' => 0],
            ],
        ];
    }

    /**
     * @dataProvider providerBuildImage
     * @covers       Onigoetz\Imagecache\Manager::buildImage
     */
    public function testBuildImageCalculation($entry, $calculated)
    {
        $manager = $this->getManager();
        $file = $this->getDummyImageName();
        $original_file = vfsStream::url('root') . '/' . $file;
        $final_file = vfsStream::url('root') . '/cache/200X/' . $file;
        $preset = [$entry];

        $image = m::mock(new Image($original_file));
        $image->shouldReceive('save')->andReturn(clone $image);

        $manager->setMethodCaller($caller = m::mock('Onigoetz\Imagecache\MethodCaller'));
        $caller->shouldReceive('call')->with($image, $entry['action'], $calculated)->andReturn(true);

        $this->assertInstanceOf('\Onigoetz\Imagecache\Image', $this->setAccessible('buildImage')->invoke($manager, $preset, $image, $final_file));
    }

    public function providerIsRetina()
    {
        return [
            [true, 'image@2x.jpg'],
            [false, 'image.new.jpg'],
            [true, 'image.new@2x.jpg'],
        ];
    }


    /**
     * @dataProvider providerIsRetina
     * @covers       Onigoetz\Imagecache\Manager::isRetina
     */
    public function testIsRetina($expected, $file)
    {
        $this->assertEquals($expected, $this->getManager()->isRetina($file));
    }

    public function providerGetOriginalFilename()
    {
        return [
            ['image.jpg', 'image@2x.jpg'],
            ['image.new.jpg', 'image.new.jpg'],
            ['image.new.jpg', 'image.new@2x.jpg'],
            ['DSC_0901.png', 'DSC_0901.png'],
            ['picture.png', 'picture@2x.png'],
            ['new_image.test.webp', 'new_image.test@2x.webp'],
            ['new_image.test.webp', 'new_image.test.webp'],
        ];
    }

    /**
     * @dataProvider providerGetOriginalFilename
     * @covers       Onigoetz\Imagecache\Manager::getOriginalFilename
     */
    public function testgetOriginalFilename($expected, $file)
    {
        $this->assertEquals($expected, $this->getManager()->getOriginalFilename($file));
    }

    /**
     * @covers Onigoetz\Imagecache\Manager::buildImage
     */
    public function testBuildImageMultiple()
    {
        $manager = $this->getManager();
        $file = $this->getDummyImageName();
        $original_file = vfsStream::url('root') . '/' . $file;
        $final_file = vfsStream::url('root') . '/cache/200X/' . $file;
        $preset = [
            ['action' => 'scale', 'width' => 200, 'height' => '200'],
            ['action' => 'crop', 'width' => 120, 'offsetx' => 'left', 'offsety' => 'top'],
        ];

        $image = m::mock(new Image($original_file));
        $image->shouldReceive('save')->andReturn(clone $image);

        $manager->setMethodCaller($caller = m::mock('Onigoetz\Imagecache\MethodCaller'));
        $caller->shouldReceive('call')->with($image, $preset[0]['action'], $preset[0])->andReturn(true);
        $caller->shouldReceive('call')->with($image, $preset[1]['action'], $preset[1])->andReturn(true);

        $this->assertInstanceOf('\Onigoetz\Imagecache\Image', $this->setAccessible('buildImage')->invoke($manager, $preset, $image, $final_file));
    }

    /**
     * @covers Onigoetz\Imagecache\Manager::buildImage
     * @expectedException \RuntimeException
     */
    public function testBuildImageManipulationFailed()
    {
        $manager = $this->getManager();
        $file = $this->getDummyImageName();
        $original_file = vfsStream::url('root') . '/' . $file;
        $final_file = vfsStream::url('root') . '/cache/200X/' . $file;
        $preset = [['action' => 'scale', 'width' => 200, 'height' => '200']];

        $manager->setMethodCaller($caller = m::mock('Onigoetz\Imagecache\MethodCaller'));
        $caller->shouldReceive('call')->andThrow(new \RuntimeException());

        $image = new Image($original_file);

        $this->setAccessible('buildImage')->invoke($manager, $preset, $image, $final_file);
    }

    /**
     * @covers Onigoetz\Imagecache\Manager::buildImage
     * @expectedException \RuntimeException
     */
    public function testBuildImageSaveFailed()
    {
        $manager = $this->getManager();
        $file = $this->getDummyImageName();
        $original_file = vfsStream::url('root') . '/' . $file;
        $final_file = vfsStream::url('root') . '/cache/200X/' . $file;
        $preset = [['action' => 'scale', 'width' => 200, 'height' => '200']];

        $image = m::mock(new Image($original_file));
        $image->shouldReceive('save')->andThrow(new \RuntimeException('can\'t write to disk'));

        $this->setAccessible('buildImage')->invoke($manager, $preset, $image, $final_file);
    }
}
