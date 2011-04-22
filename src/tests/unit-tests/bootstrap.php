<?php

// =========================================================================
//
// tests/bootstrap.php
//		A helping hand for running our unit tests
//
// Author	Stuart Herbert
//		(stuart.herbert@gradwell.com)
//
// Copyright	(c) 2010 Gradwell dot com Ltd
//		All rights reserved
//
// =========================================================================

$paths = array(realpath(__DIR__ . '/../../php'), realpath(__DIR__ . '/../../../vendor/php'), realpath(__DIR__ . '/php'));
$paths = array_merge($paths, array_map(function($path)
{
	return realpath($path);
}, explode(PATH_SEPARATOR, get_include_path())));

spl_autoload_register(function($class) use($paths)
{
	if($index = strrpos($class, '\\'))
	{
		$path = DIRECTORY_SEPARATOR.strtr(substr($class, 0, $index), '\\', DIRECTORY_SEPARATOR)
			.DIRECTORY_SEPARATOR.strtr(substr($class, $index + 1), '_', DIRECTORY_SEPARATOR).'.php';
	}
	else
	{
		$path = DIRECTORY_SEPARATOR.strtr(substr($class, $index), '_', DIRECTORY_SEPARATOR).'.php';
	}
	
	foreach($paths as $p)
	{
		if(file_exists($p.$path))
		{
			require $p.$path;
			
			return true;
		}
	}
	
	return false;
});
/*
// step 1: create the APP_TOPDIR constant that all MF components require
define('APP_TOPDIR', realpath(__DIR__ . '/../../php'));
define('APP_LIBDIR', realpath(__DIR__ . '/../../../vendor/php'));
define('APP_TESTDIR', realpath(__DIR__ . '/php'));

// step 2: add the lib-vendor code to the include path
set_include_path(APP_LIBDIR . PATH_SEPARATOR . get_include_path());

// step 3: add the tests folder to the include path
set_include_path(APP_TESTDIR . PATH_SEPARATOR . get_include_path());

// step 4: add our code to the include path
set_include_path(APP_TOPDIR . PATH_SEPARATOR . get_include_path());

// step 5: find the autoloader, and install it
require_once(APP_LIBDIR . '/gwc.autoloader.php');
