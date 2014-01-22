<?php
	
use Onigoetz\Imagecache\Manager;
	
class ManagerTest extends \PHPUnit_Framework_TestCase {
	
	function getMockedToolkit() {
		return 'gd';
	}
	
	function setAccessible($methodName) {
		$method = new ReflectionMethod('Onigoetz\Imagecache\Manager', $methodName);
		$method->setAccessible(true);
 
		return $method;
	}
	
	function testClassExists() {
		$this->assertTrue(class_exists('Onigoetz\Imagecache\Manager'));
	}
	
	function testURL() {
		$options = array('path_images' => 'img', 'path_cache' => 'cache');
		$manager = new Manager($options, $this->getMockedToolkit());
		
		$preset = 'preset';
		$file = 'file.jpg';
		
		$this->assertEquals("{$options['path_images']}/{$options['path_cache']}/$preset/$file", $manager->url($preset, $file));
	}
	
	function testImageURL() {
		$options = array('path_images' => 'img');
		$manager = new Manager($options, $this->getMockedToolkit());
		
		$file = 'file.jpg';
		
		$final = $this->setAccessible('image_url')->invoke($manager, $file);
		$this->assertEquals("{$options['path_images']}/$file", $final);
	}
	
	/*/*
     * @expectedException \Onigoetz\Imagecache\Exceptions\InvalidPresetException
     */
	/*function testNonExistingPreset() {
		$preset_content = array('scale_and_crop' => array('width' => 200));
		$options = array('presets' => array('200X' => $preset_content));
		$manager = new Manager($options, $this->getMockedToolkit());
		
		$preset = '200X';
		$file = 'file.jpg';
		
		$preset_result = $this->setAccessible('get_preset_actions')->invoke($manager, $preset, $file);
		
		$this->assertEquals($preset_content, $preset_result);
	}*/
	
	function providerPercent() {
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
	function testPercent($result, $percent, $current_value) {
		$manager = new Manager(array(), $this->getMockedToolkit());

		$this->assertEquals(
			$result,
			$this->setAccessible('percent')->invoke($manager, $percent, $current_value)
		);
	}
}