<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\ClassTools;

/**
 * Exception telling that a conflicting class name was discovered
 * by the class finder.
 */
class ClassConflictException extends \RuntimeException
{
	// TODO: Is this threshold good?
	const MAX_MESSAGE_LENGTH = 200;
	
	/**
	 * List of conflicting classes.
	 * 
	 * @var array(string)
	 */
	protected $classes   = array();
	
	/**
	 * List of files containing conflicting classes.
	 * 
	 * @var array(string)
	 */
	protected $files     = array();
	
	/**
	 * @param  array(array('class' => string, 'file' => string), ...)  List of conflicting class<->file pairs
	 */
	function __construct(array $conflicts)
	{
		$this->classes = array_map(function($elem)
		{
			return $elem['class'];
		}, $conflicts);
		
		$this->files = array_map(function($elem)
		{
			return $elem['file'];
		}, $conflicts);
		
		$this->classes = array_unique($this->classes);
		$this->files   = array_unique($this->files);
		
		$classlist = implode(', ', $this->classes);
		$filelist  = implode(', ', $this->files);
		
		if(strlen($classlist) > self::MAX_MESSAGE_LENGTH)
		{
			$classlist = substr($classlist, 0, self::MAX_MESSAGE_LENGTH).'...';
		}
		
		if(strlen($filelist) > self::MAX_MESSAGE_LENGTH)
		{
			$filelist = substr($filelist, 0, self::MAX_MESSAGE_LENGTH).'...';
		}
		
		parent::__construct('ClassFinder: Found conflicting class(es): '.$classlist.' in files: '.$filelist);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns a list of unique class names which were conflicted.
	 * 
	 * @return array(string)
	 */
	public function getConflictingClasses()
	{
		return $this->classes;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns a list of files containing conflicting classes.
	 * 
	 * @return array(string)
	 */
	public function getConflictingFiles()
	{
		return $this->files;
	}
}



/* End of file ClassConflictException.php */
/* Location: src/php/Inject/ClassTools */