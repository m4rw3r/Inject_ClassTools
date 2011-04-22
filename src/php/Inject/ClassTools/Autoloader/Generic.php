<?php
/*
 * Created by Martin Wernståhl on 2011-04-21.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\ClassTools\Autoloader;

/**
 * Generic autoloader able to search several paths for classes and also handles
 * prefixes, both Namespace and PEAR-style.
 * 
 * Example usage:
 * <code>
 * require 'Inject/ClassTools/Autoloader/Generic.php';
 * 
 * // Use defaults: No prefixes, search PHP include path
 * $loader = new Inject\ClassTools\Autoloader\Generic();
 * 
 * $loader->register();
 * </code>
 * 
 * <code>
 * require 'Inject/ClassTools/Autoloader/Generic.php';
 * 
 * // Only search current directory and specific vendor dirs:
 * $loader = new Inject\ClassTools\Autoloader\Generic(__DIR__, 
 *     array(
 *         'InjectStack' => __DIR__.'/vendor/InjectStack'
 *     ));
 * 
 * $loader->register();
 */
class Generic
{
	/**
	 * List of paths to search in.
	 * 
	 * @var array(string)
	 */
	protected $paths = array();
	
	/**
	 * Path-fragment => path mappings.
	 * 
	 * @var array(string => string)
	 */
	protected $namespaces = array();
	
	/**
	 * @param  array(string)  The paths to search, defaults to PHP's include paths
	 * @param  array(string => string)  prefix => path mappings for namespaced components in other directories
	 */
	function __construct($paths = false, array $namespaces = array())
	{
		$this->paths = empty($paths) ? explode(PATH_SEPARATOR, get_include_path()) : (Array) $paths;
		$this->paths = array_map(function($path)
		{
			return realpath($path);
		}, $this->paths);
		
		empty($namespaces) OR $this->addNamespaces($namespaces);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Adds a list of namespaces.
	 * 
	 * @param  array(string => string)  Class prefix => path
	 * @return void
	 */
	public function addNamespaces(array $namespaces)
	{
		foreach($namespaces as $prefix => $path)
		{
			$this->namespaces[trim(strtr($prefix, '\\_', DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR] = $path;
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns a list of path-fragment => path mappings for namespaces.
	 * 
	 * @return array(string => string)
	 */
	public function getNamespaces()
	{
		return $this->namespaces;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns a list of the paths this autoloader searches in.
	 * 
	 * @return array(string)
	 */
	public function getPaths()
	{
		return $this->paths;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Registers this autoloader with PHP.
	 * 
	 * @return void
	 */
	public function register()
	{
		spl_autoload_register(array($this, 'load'));
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Unregisters this autoloader with PHP.
	 * 
	 * @return void
	 */
	public function unregister()
	{
		spl_autoload_unregister(array($this, 'load'));
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Loads the specified class.
	 * 
	 * @param  string
	 * @return boolean
	 */
	public function load($class)
	{
		if($index = strrpos($class, '\\'))
		{
			$path = strtr(substr($class, 0, $index), '\\', DIRECTORY_SEPARATOR)
				.DIRECTORY_SEPARATOR.strtr(substr($class, $index + 1), '_', DIRECTORY_SEPARATOR).'.php';
		}
		else
		{
			$path = strtr(substr($class, $index), '_', DIRECTORY_SEPARATOR).'.php';
		}
		
		foreach($this->namespaces as $name => $package_path)
		{
			if(strpos($path, $name) === 0)
			{
				$file_path = $package_path.substr($path, strlen($name)-1);
				
				if(file_exists($file_path))
				{
					require $file_path;
					
					return true;
				}
			}
		}
		
		foreach($this->paths as $dir)
		{
			if(file_exists($dir.DIRECTORY_SEPARATOR.$path))
			{
				require $dir.DIRECTORY_SEPARATOR.$path;
				
				return true;
			}
		}
		
		return false;
	}
}

/* End of file Generic.php */
/* Location: src/php/Inject/ClassTools/Autoloader */