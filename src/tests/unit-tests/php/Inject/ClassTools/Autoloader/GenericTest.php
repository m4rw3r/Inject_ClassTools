<?php
/*
 * Created by Martin Wernståhl on 2011-04-21.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\ClassTools\Autoloader;

/**
 * 
 */
class GenericTest extends \PHPUnit_Framework_TestCase
{
	public function testInstance()
	{
		$loader = new Generic();
		
		$this->assertTrue($loader instanceof Generic);
	}
	public function testDefaultNamespaces()
	{
		$loader = new Generic();
		
		$this->assertEquals(array(), $loader->getNamespaces());
	}
	public function testNamespaces()
	{
		$loader = new Generic(null, array('Test' => __DIR__, 'Test_Second' => __DIR__, 'Test\\Third' => __DIR__));
		
		$this->assertEquals(array(
				'Test'.DIRECTORY_SEPARATOR => __DIR__,
				'Test'.DIRECTORY_SEPARATOR.'Second'.DIRECTORY_SEPARATOR => __DIR__,
				'Test'.DIRECTORY_SEPARATOR.'Third'.DIRECTORY_SEPARATOR => __DIR__
			), $loader->getNamespaces());
	}
	public function testDefaultPaths()
	{
		$loader = new Generic();
		
		$realpath_include = array_map(function($path)
		{
			return realpath($path);
		}, explode(PATH_SEPARATOR, get_include_path()));
		
		$this->assertEquals($realpath_include, $loader->getPaths());
	}
	public function testPaths()
	{
		$loader = new Generic(__DIR__);
		
		$this->assertEquals(array(__DIR__), $loader->getPaths());
	}
	public function testPaths2()
	{
		$loader = new Generic(array(__DIR__, __DIR__.'/..'));
		
		$this->assertEquals(array(__DIR__, realpath(__DIR__.'/..')), $loader->getPaths());
	}
	/**
	 * @runInSeparateProcess true
	 */
	public function testLoadClass()
	{
		$loader = new Generic(__DIR__.'/ExampleClasses', array(
				'Deep\\Leveled\\Ns' => __DIR__.'/ExampleClasses/DeepNamespace',
				'PEAR_Ns' => __DIR__.'/ExampleClasses/PEARNs',
				'Strange' => __DIR__.'/ExampleClasses/SomeStrangeNamespace'
			));
		
		$classes = array(
			'Root'                    => 'Root.php',
			'Strange\\Root'           => 'SomeStrangeNamespace/Root.php',
			'Strange\\Dir\\Next'      => 'SomeStrangeNamespace/Dir/Next.php',
			'PEAR_Ns_Root'            => 'PEARNs/Root.php',
			'PEAR_Ns_Nested_Class'    => 'PEARNs/Nested/Class.php',
			'PEAR_Root'               => 'PEAR/Root.php',
			'Ns\\Namespaced'          => 'Ns/Namespaced.php',
			'Ns\\Namespaced\\Deep'    => 'Ns/Namespaced/Deep.php',
			'Deep\\Leveled\\Ns\\Root' => 'DeepNamespace/Root.php',
			'Strange\\NotNsed'        => 'Strange/NotNsed.php'
		);
		
		foreach($classes as $class => $file)
		{
			$this->assertFalse(class_exists($class));
			$this->assertTrue($loader->load($class));
			$this->assertTrue(class_exists($class));
			
			$this->assertEquals(__DIR__.'/ExampleClasses/'.$file, strtr($class::getFile(), '\\', '/'));
		}
		
		$this->assertFalse($loader->load('NotExistingClass'));
	}
	public function testRegister()
	{
		$loader = new Generic();
		
		$loader->register();
		
		$this->assertContains(array($loader, 'load'), spl_autoload_functions());
		
		$loader->unregister();
		
		$this->assertNotContains(array($loader, 'load'), spl_autoload_functions());
	}
}

/* End of file GenericTest.php */
/* Location: src/php/Inject/ClassTools/Autoloader */