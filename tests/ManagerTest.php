<?php
	
use Onigoetz\Imagecache\Manager;
	
class ManagerTest extends \PHPUnit_Framework_TestCase {
	
	function getMockedToolkit() {
		return 'gd';
	}
	
	function testClassExists() {
		$this->assertTrue(class_exists('Onigoetz\Imagecache\Manager'));
	}
	
	function testURL() {
		$options = array('path_images' => 'img', 'path_cache' => 'cache');
		$manager = new Manager($options, $this->getMockedToolkit());
		
		$preset = 'preset';
		$file = 'file.jpg';
		
		$this->assertEquals($manager->url($preset, $file), "{$options['path_images']}/{$options['path_cache']}/$preset/$file");
	}
}