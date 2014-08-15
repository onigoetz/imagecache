<?php

use Mockery as m;
use Onigoetz\Imagecache\Image;
use Onigoetz\Imagecache\Manager;
use Onigoetz\Imagecache\MethodCaller;
use org\bovigo\vfs\vfsStream;

class ManagerTest extends ImagecacheTestCase
{

    function setAccessible($methodName)
    {
        $method = new ReflectionMethod('Onigoetz\Imagecache\Manager', $methodName);
        $method->setAccessible(true);

        return $method;
    }

    function testClassExists()
    {
        $this->assertTrue(class_exists('Onigoetz\Imagecache\Manager'));
    }

    function testGetMethodCaller()
    {
        $manager = $this->getManager();

        $this->assertInstanceOf('\Onigoetz\Imagecache\MethodCaller', $manager->getMethodCaller());
    }

    function testSetMethodCaller()
    {
        $manager = $this->getManager();

        $manager->setMethodCaller($methodCaller = new MethodCaller);

        $this->assertInstanceOf('\Onigoetz\Imagecache\MethodCaller', $manager->getMethodCaller());
        $this->assertSame($methodCaller, $manager->getMethodCaller());
    }

    /**
     * @expectedException \Onigoetz\Imagecache\Exceptions\InvalidPresetException
     */
    function testNonExistingPreset()
    {
        $manager = $this->getManager(array('presets' => array()));

        $this->setAccessible('getPresetActions')->invoke($manager, '200X', 'file.jpg');
    }

    function testGetPreset()
    {
        $preset = array(array('action' => 'scale_and_crop', 'width' => 40, 'height' => 40));

        $manager = $this->getManager(array('presets' => array('200X' => $preset)));

        list($resolved_presets) = $this->setAccessible('getPresetActions')->invoke($manager, '200X', 'file.jpg');

        $this->assertEquals($preset, $resolved_presets);
    }

    function testGetRetinaPreset()
    {
        $original_preset = array(array('action' => 'scale_and_crop', 'width' => 40, 'height' => 40));
        $original_preset_key = '200X';
        $original_file = 'file@2x.jpg';

        $manager = $this->getManager(array('presets' => array('200X@2x' => $original_preset, '200X' => array())));

        list($resolved_preset, $resolved_key, $resolved_file) = $this->setAccessible('getPresetActions')->invoke(
            $manager,
            $original_preset_key,
            $original_file
        );

        $this->assertEquals($original_preset, $resolved_preset);
        $this->assertEquals('200X@2x', $resolved_key);
        $this->assertEquals('file.jpg', $resolved_file);
    }

    function providerRetinaGenerator()
    {
        return array(
            array(
                array('action' => 'scale_and_crop', 'width' => 40, 'height' => 40),
                array('action' => 'scale_and_crop', 'width' => 80, 'height' => 80)
            ),
            array(
                array('action' => 'scale', 'width' => 40),
                array('action' => 'scale', 'width' => 80)
            ),
            array(
                array('action' => 'scale', 'width' => 40),
                array('action' => 'scale', 'width' => 80)
            ),
            array(
                array('action' => 'scale', 'width' => '50%'),
                array('action' => 'scale', 'width' => '50%')
            ),
            array(
                array('action' => 'crop', 'width' => '50%', 'yoffset' => 20),
                array('action' => 'crop', 'width' => '50%', 'yoffset' => 40)
            ),
            array(
                array('action' => 'crop', 'width' => '20', 'yoffset' => 'top'),
                array('action' => 'crop', 'width' => '40', 'yoffset' => 'top')
            ),
            array(
                array('action' => 'crop', 'width' => '50%', 'xoffset' => 20),
                array('action' => 'crop', 'width' => '50%', 'xoffset' => 40)
            ),
            array(
                array('action' => 'crop', 'width' => '20', 'xoffset' => 'left'),
                array('action' => 'crop', 'width' => '40', 'xoffset' => 'left')
            ),
        );
    }

    /**
     * @dataProvider providerRetinaGenerator
     */
    function testGenerateRetinaAction($original, $generated)
    {
        $manager = $this->getManager();

        $this->assertEquals($generated, $this->setAccessible('generateRetinaAction')->invoke($manager, $original));
    }

    function testGenerateRetinaPreset()
    {
        $original_preset = array(array('action' => 'scale', 'width' => 40), array('action' => 'scale', 'width' => 60));
        $generated_preset = array(
            array('action' => 'scale', 'width' => 80),
            array('action' => 'scale', 'width' => 120)
        );
        $original_preset_key = '200X';
        $original_file = 'file@2x.jpg';

        $manager = $this->getManager(array('presets' => array('200X' => $original_preset)));

        list($resolved_preset, $resolved_key, $resolved_file) = $this->setAccessible('getPresetActions')->invoke(
            $manager,
            $original_preset_key,
            $original_file
        );

        $this->assertEquals($generated_preset, $resolved_preset);
        $this->assertEquals('200X@2x', $resolved_key);
        $this->assertEquals('file.jpg', $resolved_file);
    }

    function testLoadImage()
    {
        $manager = $this->getManager();

        $this->assertInstanceOf(
            'Onigoetz\Imagecache\Image',
            $this->setAccessible('loadImage')->invoke($manager, 'vfs://root/images/' . $this->getDummyImageName())
        );
    }

    /**
     * @expectedException \Onigoetz\Imagecache\Exceptions\NotFoundException
     * @covers Onigoetz\Imagecache\Manager::handleRequest
     */
    function testNonExistingFile()
    {
        $manager = $this->getManager(array('presets' => array('200X' => array())));

        $manager->handleRequest('200X', 'file.jpg');
    }

    /**
     * @covers Onigoetz\Imagecache\Manager::handleRequest
     */
    function testAlreadyExists()
    {
        $preset = '200X';
        $file = $this->getDummyImageName();
        $final_file = 'vfs://root/images/cache/' . $preset . '/' . $file;
        $dir = dirname($final_file);

        $manager = $this->getMockedManager(array('presets' => array($preset => array())));

        //Create file
        mkdir($dir, 0755, true);
        touch($final_file);

        $manager->shouldReceive('buildImage')->andReturn(false);

        $this->assertEquals($final_file, $manager->handleRequest($preset, $file));
    }

    /**
     * @covers Onigoetz\Imagecache\Manager::handleRequest
     */
    function testHandleRequestCreateDirectory()
    {
        $preset = '200X';
        $file = $this->getDummyImageName();

        $manager = $this->getMockedManager(array('presets' => array($preset => array())));

        $this->assertNull($this->vfsRoot->getChild('images')->getChild('cache'));

        $manager->shouldReceive('buildImage')->andReturn(true);

        $manager->handleRequest($preset, $file);

        $this->assertEquals(
            0755,
            $this->vfsRoot->getChild('images')->getChild('cache')->getChild($preset)->getPermissions()
        );
    }

    /**
     * @covers Onigoetz\Imagecache\Manager::handleRequest
     */
    function testHandleRequestCannotLoadImage()
    {
        $preset = '200X';

        $manager = $this->getMockedManager(array('presets' => array($preset => array())));
        $manager->shouldReceive('loadImage')->andReturn(false);

        $this->assertFalse($manager->handleRequest($preset, $this->getDummyImageName()));
    }

    /**
     * @covers Onigoetz\Imagecache\Manager::handleRequest
     */
    function testHandleRequest()
    {
        $file = $this->getDummyImageName();
        $preset = '200X';
        $expected = array(
            'original_file' => 'vfs://root/images/' . $file,
            'preset' => array(),
            'final_file' => 'vfs://root/images/cache/' . $preset . '/' . $file,
        );
        $expected['image'] = new Image($expected['original_file'], $this->getMockedToolkit());

        $manager = $this->getMockedManager(array('presets' => array($preset => $expected['preset'])));

        $manager->shouldReceive('loadImage')->with($expected['original_file'])->andReturn($expected['image']);
        $manager->shouldReceive('buildImage')->with($expected['preset'], $expected['image'], $expected['final_file'])
            ->andReturn(true);

        $manager->handleRequest($preset, $file);
    }

    /**
     * @covers Onigoetz\Imagecache\Manager::handleRequest
     */
    function testHandleFailedRequest()
    {
        $file = $this->getDummyImageName();
        $preset = '200X';

        $manager = $this->getMockedManager(array('presets' => array($preset => array())));

        $manager->shouldReceive('buildImage')->andReturn(false);

        $manager->handleRequest($preset, $file);
    }

    function providerBuildImage()
    {
        //the example image is 500x500

        return array(
            array(
                array('action' => 'scale_and_crop', 'width' => 40, 'height' => '25%'),
                array('action' => 'scale_and_crop', 'width' => 40, 'height' => 125),
            ),
            array(
                array('action' => 'scale', 'width' => 40),
                array('action' => 'scale', 'width' => 40)
            ),
            array(
                array('action' => 'scale', 'width' => '50%'),
                array('action' => 'scale', 'width' => 250)
            ),
            array(
                array('action' => 'crop', 'height' => '50%', 'yoffset' => 20),
                array('action' => 'crop', 'height' => 250, 'yoffset' => 20)
            ),
            array(
                array('action' => 'crop', 'height' => 20, 'yoffset' => 'bottom'),
                array('action' => 'crop', 'height' => 20, 'yoffset' => 480)
            ),
            array(
                array('action' => 'crop', 'width' => '50%', 'xoffset' => 'center'),
                array('action' => 'crop', 'width' => '250', 'xoffset' => 125)
            ),
            array(
                array('action' => 'crop', 'width' => '20', 'xoffset' => 'left'),
                array('action' => 'crop', 'width' => '20', 'xoffset' => 0)
            ),
        );
    }

    /**
     * @dataProvider providerBuildImage
     * @covers       Onigoetz\Imagecache\Manager::buildImage
     */
    function testBuildImageCalculation($entry, $calculated)
    {
        $manager = $this->getManager();
        $file = $this->getDummyImageName();
        $original_file = vfsStream::url('root/images') . '/' . $file;
        $final_file = vfsStream::url('root/images') . '/cache/200X/' . $file;
        $preset = array($entry);

        $image = m::mock(new Image($original_file, $this->getMockedToolkit()));
        $image->shouldReceive('save')->andReturn(true);

        $manager->setMethodCaller($caller = m::mock('Onigoetz\Imagecache\MethodCaller'));
        $caller->shouldReceive('call')->with($image, $entry['action'], $calculated)->andReturn(true);

        $this->assertTrue($this->setAccessible('buildImage')->invoke($manager, $preset, $image, $final_file));
    }

    /**
     * @covers Onigoetz\Imagecache\Manager::buildImage
     */
    function testBuildImageMultiple()
    {
        $manager = $this->getManager();
        $file = $this->getDummyImageName();
        $original_file = vfsStream::url('root/images') . '/' . $file;
        $final_file = vfsStream::url('root/images') . '/cache/200X/' . $file;
        $preset = array(
            array('action' => 'scale', 'width' => 200, 'height' => '200'),
            array('action' => 'crop', 'width' => 120, 'offsetx' => 'left', 'offsety' => 'top')
        );

        $image = m::mock(new Image($original_file, $this->getMockedToolkit()));
        $image->shouldReceive('save')->andReturn(true);

        $manager->setMethodCaller($caller = m::mock('Onigoetz\Imagecache\MethodCaller'));
        $caller->shouldReceive('call')->with($image, $preset[0]['action'], $preset[0])->andReturn(true);
        $caller->shouldReceive('call')->with($image, $preset[1]['action'], $preset[1])->andReturn(true);



        $this->assertTrue($this->setAccessible('buildImage')->invoke($manager, $preset, $image, $final_file));
    }

    /**
     * @covers Onigoetz\Imagecache\Manager::buildImage
     */
    function testBuildImageCannotSave()
    {
        $manager = $this->getManager();
        $file = $this->getDummyImageName();
        $original_file = vfsStream::url('root/images') . '/' . $file;
        $final_file = vfsStream::url('root/images') . '/cache/200X/' . $file;
        $preset = array(array('action' => 'scale', 'width' => 200, 'height' => '200'));

        $image = m::mock(new Image($original_file, $this->getMockedToolkit()));
        $image->shouldReceive('save')->andReturn(false);

        $this->assertFalse($this->setAccessible('buildImage')->invoke($manager, $preset, $image, $final_file));
    }

    /**
     * @covers Onigoetz\Imagecache\Manager::buildImage
     */
    function testBuildImageManipulationFailed()
    {
        $manager = $this->getManager();
        $file = $this->getDummyImageName();
        $original_file = vfsStream::url('root/images') . '/' . $file;
        $final_file = vfsStream::url('root/images') . '/cache/200X/' . $file;
        $preset = array(array('action' => 'scale', 'width' => 200, 'height' => '200'));

        $manager->setMethodCaller($caller = m::mock('Onigoetz\Imagecache\MethodCaller'));
        $caller->shouldReceive('call')->andReturn(false);

        $image = new Image($original_file, $this->getMockedToolkit());

        $this->assertFalse($this->setAccessible('buildImage')->invoke($manager, $preset, $image, $final_file));
    }

    /**
     * @covers Onigoetz\Imagecache\Manager::buildImage
     */
    function testBuildImageSaveFailed()
    {
        $manager = $this->getManager();
        $file = $this->getDummyImageName();
        $original_file = vfsStream::url('root/images') . '/' . $file;
        $final_file = vfsStream::url('root/images') . '/cache/200X/' . $file;
        $preset = array(array('action' => 'scale', 'width' => 200, 'height' => '200'));

        $image = m::mock(new Image($original_file, $this->getMockedToolkit()));
        $image->shouldReceive('save')->andReturn(false);

        $this->assertFalse($this->setAccessible('buildImage')->invoke($manager, $preset, $image, $final_file));
    }
}
