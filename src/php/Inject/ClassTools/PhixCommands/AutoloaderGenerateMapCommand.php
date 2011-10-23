<?php


namespace Inject\ClassTools\PhixCommands;

use Exception;
use Phix_Project\Phix\CommandsList;
use Phix_Project\Phix\Context;
use Phix_Project\PhixExtensions\CommandBase;
use Phix_Project\PhixExtensions\CommandInterface;
use Phix_Project\CommandLineLib\DefinedSwitches;
use Phix_Project\CommandLineLib\DefinedSwitch;
use Phix_Project\CommandLineLib\CommandLineParser;
use Phix_Project\ValidationLib\MustBeValidPath;
use Inject\ClassTools\ClassConflictException;
use Inject\ClassTools\ClassFinder;

/**
 * 
 */
class AutoloaderGenerateMapCommand extends CommandBase implements CommandInterface
{
	public function getCommandName()
	{
		return 'autoloader:generate-map';
	}
	
	public function getCommandDesc()
	{
		return 'creates an autoloader which loads class-files using a precompiled class->file map';
	}
	
	public function getCommandOptions()
	{
		$options = new DefinedSwitches();
		
		$options->addSwitch('fileRegex', 'The regular expression used to search for class-files')
		        ->setWithLongSwitch('fileRegex')
		        ->setWithOptionalArg('<fileRegex>', 'The regular expression used to search for class-files')
		        ->setArgHasDefaultValueOf('/\.php$/');
		
		$options->addSwitch('namespace', 'The class namespace of the generated autoloader')
		        ->setWithLongSwitch('namespace')
		        ->setWithOptionalArg('<namespace>', 'The class namespace of the generated autoloader')
		        ->setArgHasDefaultValueOf(false);
		
		$options->addSwitch('className', 'The class name of the generated autoloader class')
		        ->setWithLongSwitch('className')
		        ->setWithOptionalArg('<className>', 'The class name of the generated autoloader class')
		        ->setArgHasDefaultValueOf('MapLoader');
		
		$options->addSwitch('output', 'The output file to put the autoloader in')
		        ->setWithShortSwitch('o')
		        ->setWithLongSwitch('output')
		        ->setWithOptionalArg('<output>', 'The output file to put the autoloader in')
		        ->setArgHasDefaultValueOf('MapLoader.php');
		
		$options->addSwitch('autoRegister', 'If to call spl_autoload_register() in the file when using require')
		        ->setWithShortSwitch('r')
		        ->setWithLongSwitch('autoRegister');
		
		return $options;
	}
	
	public function getCommandArgs()
	{
		return array(
			'<paths> ...' => 'A list of paths to search for class files to include in the autoloader map'
		);
	}
	
	public function validateAndExecute($args, $argsIndex, Context $context)
	{
		$so = $context->stdout;
		$se = $context->stderr;
		
		// step 1: parse the options
		$options  = $this->getCommandOptions();
		$parser	  = new CommandLineParser();
		list($parsedSwitches, $argsIndex) = $parser->parseSwitches($args, $argsIndex, $options);
		
		// step 2: verify the args
		$errors = $parsedSwitches->validateSwitchValues();
		if (count($errors) > 0)
		{
			// validation failed
			foreach ($errors as $errorMsg)
			{
				$se->output($context->errorStyle, $context->errorPrefix);
				$se->outputLine(null, $errorMsg);
			}
			
			// return the error code to the caller
			return 1;
		}
		
		$output       = $parsedSwitches->getFirstArgForSwitch('output');
		$className    = $parsedSwitches->getFirstArgForSwitch('className');
		$callRegister = $parsedSwitches->testHasSwitch('autoRegister');
		$fileRegex    = $parsedSwitches->getFirstArgForSwitch('fileRegex');
		$namespace    = $parsedSwitches->getFirstArgForSwitch('namespace');
		
		$paths = array_slice($args, $argsIndex);
		
		if(empty($paths))
		{
			$se->output($context->errorStyle, $context->errorPrefix);
			$se->outputLine(null, 'No paths has been specified to search, please specify them after the command line switches separated by spaces');
			
			return 1;
		}
		
		try
		{
			$finder = new ClassFinder($paths, $fileRegex);
			
			$classes = $finder->getClassFiles();
		}
		catch(ClassConflictException $e)
		{
			$classes = $finder->getClassFiles();
			
			$se->output($context->errorStyle, $context->errorPrefix);
			$se->outputLine(null, $e->getMessage());
		}
		catch(Exception $e)
		{
			$se->output($context->errorStyle, $context->errorPrefix);
			$se->outputLine(null, $e->getMessage());
			
			return 1;
		}
		
		$map = var_export($classes, true);
		
		$file =<<<EOF
class $className
{
	protected static \$map = $map;
	
	public function load(\$class)
	{
		if(isset(self::\$map[\$class]))
		{
			require self::\$map[\$class];
			
			return true;
		}
		
		return false;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Registers this autoloader with PHP.
	 * 
	 * @return void
	 */
	public function register()
	{
		spl_autoload_register(array(\$this, 'load'));
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Unregisters this autoloader with PHP.
	 * 
	 * @return void
	 */
	public function unregister()
	{
		spl_autoload_unregister(array(\$this, 'load'));
	}
}
EOF;
		
		if($namespace)
		{
			$file = "<?php\n\nnamespace $namespace;\n\n".$file;
		}
		else
		{
			$file = "<?php\n\n".$file;
		}
		
		if($callRegister)
		{
			$so->outputLine(null, 'Generated autoloader will automatically register itself, no need to instantiate it and call register().');
			
			$file .= "\n\n\$___map_autoloader = new $className();\n\$___map_autoloader->register();";
		}
		
		file_put_contents($output, $file);
		
		$so->outputLine(null, 'Created Autoloader-map in "'.$output.'"');
		
		return 0;
	}
}
