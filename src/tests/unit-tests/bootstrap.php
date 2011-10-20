<?php

$paths = array(realpath(__DIR__ . '/../../php'), realpath(__DIR__ . '/../../../vendor/php'), realpath(__DIR__ . '/php'));
$paths = array_merge($paths, array_map(function($path)
{
	return realpath($path);
}, explode(PATH_SEPARATOR, get_include_path())));

spl_autoload_register(function($class) use($paths)
{
	$class = ltrim($class, '\\');
	
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
