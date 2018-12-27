<?php

/*
 * Logger.php
 *
 * Copyright(C) 2015-17 Erik Kalkoken
 *
 * Class for handling logging in a php script. 
 * Implemented as static class to enable easy usage without the need for dependency injection or use of global variables
 *
 * HISTORY:
 * 27-DEC-2018 v1.18 Fix: Typo in RuntimeException
 * 15-SEP-2017 v1.17 Fix: filesize throws exception if file does not exist, some refactoring
 * 23-JAN-2017 v1.16 New: added new method getUniqueId()
 * 06-AUG-2017 v1.15 New: added new method addUniqueId()
 * 22-MAR-2017 v1.14 Fix: validation for area did not work proberly
 * 14-MAR-2017 v1.13 Change: code overhaul: changed log() to public, added input validations and comments, refactoring
 * 23-APR-2016 v1.12 Fix: setLogLevel will now only log the new lovLevel if it actually has been changed
 * 23-MAR-2016 v1.11 Fix: <span> tag for yellow markup was send to browser even if not used
 * 18-MAR-2016 v1.10 Fix: setLogLevelByName() is no longer case sensitive
 * 05-FEB-2016 v1.9 Refactoring of logLevelNameToId(), logLevelIdToName()
 * 20-JAN-2016 v1.8 Bugfix: Wil now also log correctly when called in a destructor
 * 17-JAN-2016 v1.7 Added: MirrorToBrowser feature. Can be used globally or for each log statement
 * 11-JAN-2015 v1.6 Function names will now ony be shown when loglevel is set to debug or trace or show caller function is set to true
 * 09-JAN-2015 v1.5 Added Log Level "system" to enable message to be always shown, regardless of current log level
 * 06-JAN-2016 v1.4 Modified: Works now with multiple tags, old single tag approach replaced
 * 05-JAN-2016 v1.3 Bugfix: Function names where not shown in some cases
 * 30-DEC-2015 v1.2 Added new Log levels OFF and TRACE, added automatic log rotation, some fixes
 * 29-DEC-2015 v1.1 Added automatic detection of calling function
 * 28-DEC-2015 v1.0 First working version
 *
 * USAGE:
 * Logger::initialize("myapplication.log", Logger::LEVEL_INFO);		// Initialized the logger, should be called once
 * Logger::error("An error occured");								// Outputs a message on ERROR level to the log
 *
 * OUTPUT:
 * [yyyy-mm-dd hh:ii:ss] [Level] {Tag} Script_name.php: <class::method> (area) This is the log message
 * Note: {Tag} and (area) are optional, class and method are only shown at log level DEBUG or TRACE
 *
**/


class Logger
{
	// for log messages that always need to be shown, regardless of the current log level
	const LEVEL_SYSTEM = 1;	
	
	// turns off logging
	const LEVEL_OFF = 2;	
	
	// errors level
	const LEVEL_ERROR = 3;
	
	// warnings level
	const LEVEL_WARN = 4;
	
	// info level
	const LEVEL_INFO = 5;
	
	// debug level
	const LEVEL_DEBUG = 6;
	
	// trace level
	const LEVEL_TRACE = 7;
	
	// list of all available log levels
	const LOG_LEVELS = [
		self::LEVEL_SYSTEM,
		self::LEVEL_OFF,
		self::LEVEL_ERROR,
		self::LEVEL_WARN,
		self::LEVEL_INFO,
		self::LEVEL_DEBUG,
		self::LEVEL_TRACE,
	];
	
	// default log level used, e.g. when initialization is not done
	const DEFAULT_LOG_LEVEL = self::LEVEL_INFO;
	
	// default file name for log output
	const DEFAULT_FILE_NAME = "logfile.log";
		
	// default max size of a logfile in KB
	// logfiles will be rotated when they reach this threshold
	const DEFAULT_MAX_FILESIZE = 51200;
	
	// output format for Logger timestamp 
	const LOGGER_DATEFORMAT = "Y-m-d H:i:s";
	
	// current log level
	static private $logLevel = self::DEFAULT_LOG_LEVEL;
	
	// filename of log file
	static private $fileName = self::DEFAULT_FILE_NAME;
	
	// flag to indicate wether the Logger was initialized
	static private $initialized = false;
	
	// optional tags that will be added to every log message, e.g. current session ID
	static private $tags = null;		
	
	// flag indicating if the logger is turned on or off
	static private $activated = true;
	
	// flag to indicate if caller function should be shown along the log message
	static private $showCallerFunction = false;	
	
	// current max filesize for the current log file
	static private $maxFilesize = self::DEFAULT_MAX_FILESIZE;
	
	// flag to indicate if all log messages are mirrored to the browser
	static private $mirrorToBrowser = false;
	
	// unique ID used to reference log sessions
	static private $uniqueId = null;
	
	// Initializes the Logger with a filename and log level
	// Should be called at the beginning of each programm to clearly define the Logger
	// If not used the Logger will write to the default log file with the default log level
	public static function initialize(
		$fileName, 
		$logLevel = self::DEFAULT_LOG_LEVEL, 
		$maxFilesize = null 
	){
		// input validation
		if (is_null($fileName) 
			|| !is_string($fileName) 
			|| (strlen($fileName) == 0) 
		){
			throw new InvalidArgumentException(
				"fileName must be string and can not be null or empty"
			);
		}
		
		if (!in_array($logLevel, self::LOG_LEVELS))
		{
			throw new InvalidArgumentException(
				"Invalid logLevel '$logLevel'"
			);
		}
		
		// set filename for log file
		self::$fileName = @getcwd () . "/" . $fileName;
		if (self::$fileName === false)
		{
			throw new RuntimeException(
				"Failed to get the current path"
			);
		}
		
		self::$logLevel = $logLevel;
		self::$initialized = true;
		if ( isset($maxFilesize) 
			&& is_numeric($maxFilesize) 
			&& ($maxFilesize > 0) 
		){
			self::$maxFilesize = $maxFilesize;
		}
		
		// rotate log file if too big
		try
		{
			$size = ceil(@filesize(self::$fileName) / 1024);
		}
		catch (Exception $ex)
		{
			$size = 0;
		}
		
		if ($size > self::$maxFilesize)
		{
			// new name is old name + timestamp + .log
			$path = pathinfo(self::$fileName, PATHINFO_DIRNAME);			
			$path = ($path == ".") 
				? "" 
				// don't use current directory for path
				// also need to convert backslashes to make it system agnostic
				: str_replace('\\', '/', $path) . "/";	
			$newname = $path 
				. pathinfo(self::$fileName, PATHINFO_FILENAME) 
				. "_" . date("YmdHis") . ".log";
		
			if (@rename(self::$fileName, $newname) === true)
			{
				self::system(
					"Previous logfile had exceeded the size limit of " 
					. self::$maxFilesize ." KB and has been renamed to '" 
					. $newname ."'"
				);
			}
		}
		
	}
	
	// getter
	public static function getFileName() 			
	{
		 return self::$fileName; 
	}
	
	public static function getInitialised() 		
	{
		 return self::$initialized; 
	}

	public static function isInitialised() 			
	{
		 return self::$initialized; 
	}

	public static function getMirrorToBrowser() 	
	{
		 return self::$mirrorToBrowser; 
	}

	public static function getLogLevel() 			
	{
		 return self::$logLevel; 
	}

	public static function getLogLevelId() 			
	{
		 return self::$logLevel; 
	}

	public static function getLogLevelName() 		
	{
		 return self::logLevelIdToName(self::getLogLevel()); 
	}

	public static function getTags() 				
	{
		 return self::$tags; 
	}

	public static function getUniqueId() 			
	{
		 return self::$uniqueId; 
	}

	
	// setters
	public static function setActivated( $value ) 			
	{
		 self::$activated = boolval($value); 
	}

	public static function setMirrorToBrowser( $value ) 	
	{
		 self::$mirrorToBrowser = boolval($value); 
	}

	public static function setLogLevelByName( $name ) 		
	{
		 return self::setLogLevel(self::logLevelNameToId($name)); 
	}

	public static function setUniqueId($id) 				
	{
		 self::$uniqueId = $id; 
	}

	
	// sets the log level
	// will generate a syste log entry if log level is effectively changed
	// returns true if change was successful (incl. no change) or false on error
	public static function setLogLevel( $logLevel )
	{		
		$oldLogLevel =self::getLogLevel();
		if (in_array($logLevel, self::LOG_LEVELS))
		{
			self::$logLevel = $logLevel;
			$success = true;
		}
		else
		{
			$success = false;
		}
		
		if ($oldLogLevel != self::getLogLevel())
		{
			self::system("Loglevel set to " . self::logLevelIdToName($logLevel));
		}

		return $success;	
	}
	
	// sets the caller function
	// will generate a system log entry about the change
	public static function setShowCallerFunction( $value )
	{	
		self::system(
			"Show caller functions is set to: " . var_export ($value, true)
		);
		self::$showCallerFunction = filter_var($value, FILTER_VALIDATE_BOOLEAN);
	}
	
	// methods
	
	// turns off / pauses logging
	public static function turnOff() 
	{
		self::setActivated(false); 
	}
	
	// turns on logging
	public static function turnOn()  
	{
		self::setActivated(true);
	}
	
	// adds a tag to the Logger
	// multipe tags are possible, but the same tag can only be used once
	// returns true on success and false on error
	public static function addTag( $tag ) 
	{ 
		if ( is_null(self::getTags()) 
			|| !in_array($tag, self::getTags())
		){
			self::$tags[] = $tag; 
			return true;
		}
		else
		{
			return false;
		}
	}
	
	// add an unique ID as tagto be used for distinguiding logging between parallel instances
	// returns id if successful (needed for potential later removal)
	// returns false on error
	public static function addUniqueId()
	{
		$id = hash("md5", uniqid(rand(), true));
		$response = self::addTag($id);
		if ($response !== false)
		{
			self::setUniqueId($id);
			return $id;
		}
		else
		{		
			return false;
		}
	}
	
	// removes an existing tag from the Logger
	// returns true if the tag was removed
	// returns false if the tag was not found
	public static function removeTag( $tag )
	{
		$id = array_search(
			$tag, 
			!is_null(self::getTags()) 
				? self::getTags() 
				: []
		);
		if($id !== false)
		{
			unset(self::$tags[$id]);
			return true;
		}
		else
		{
			return false;
		}
	}
	
	// returns an array of all supported log levels as names
	public static function getSupportedLogLevels()
	{
		$list = array();
		for ($i=self::LEVEL_ERROR; $i<=self::LEVEL_TRACE; $i++)
		{
			$list[]=self::logLevelIdToName($i);
		}
		return $list;
	}
		
	// generate a log message with the respective log level
	// $msg: the log message
	// $area: to add an optional tag to the log message (optional)
	// $mirror: will mirror the current log message to the browser if true, default is false (optional)
	public static function system( $msg, $area = null, $mirror = false )	
	{
		 self::log ($msg, self::LEVEL_SYSTEM, $area, $mirror); 
	}
	
	public static function error( $msg, $area = null, $mirror = false ) 	
	{
		 self::log ($msg, self::LEVEL_ERROR, $area, $mirror); 
	}
	
	public static function warn( $msg, $area = null, $mirror = false ) 	
	{
		 self::log ($msg, self::LEVEL_WARN, $area, $mirror); 
	}
	
	public static function info( $msg, $area = null, $mirror = false ) 	
	{
		 self::log ($msg, self::LEVEL_INFO, $area, $mirror); 
	}
	
	public static function debug( $msg, $area = null, $mirror = false )	
	{
		 self::log ($msg, self::LEVEL_DEBUG, $area, $mirror); 
	}
	
	public static function trace( $msg, $area = null, $mirror = false ) 	
	{
		 self::log ($msg, self::LEVEL_TRACE, $area, $mirror); 
	}

	
	public static function log(
		$msg, 
		$logLevel = self::DEFAULT_LOG_LEVEL, 
		$area = null, 
		$mirror = false 
	){
		// validate input for $msg
		if (is_null($msg) || !is_string($msg) || (strlen($msg) == 0) )
		{
			throw new InvalidArgumentException(
				"msg must be string and can not be null or empty"
			);
		}
		
		// validate input for $loglevel
		if (!in_array($logLevel, self::LOG_LEVELS))
		{
			throw new InvalidArgumentException(
				"Invalid logLevel '$logLevel'"
			);
		}
		
		// validate input for $area
		// if (!is_null($area) && ( !is_string($area) || (strlen($area) == 0) ) ) throw new InvalidArgumentException("area must be string and can not be empty");
		
		// validate input for $mirror
		if (!is_bool($mirror)) throw new InvalidArgumentException("mirror must be boolean");
		
		if (self::$activated && (self::getLogLevel() >= $logLevel) )
		{		
			$output = "[" . date(self::LOGGER_DATEFORMAT) . "] ";
			$output .= "[" . self::logLevelIdToName ($logLevel) . "] ";
			
			if ( self::getTags() !== null )
			{
				foreach (self::getTags() as $tag) $output .= "{" . $tag . "} ";
			}	
			
			$trace = debug_backtrace();
			$output .= self::getTraceFilename ($trace) . ": ";
			
			// Function names will ony be shown when loglevel is set to debug 
			// or trace or show caller function is set to true
			if ( ( (self::getLogLevel() == self::LEVEL_DEBUG) 
				|| (self::getLogLevel() == self::LEVEL_TRACE) ) 
					|| (self::$showCallerFunction === true) )
			{
				$output .= self::getTraceFunctionName ($trace);		
			}
		
			if (isset ($area))
			{
				$output .= "(" . $area . ") ";
			}	
			
			$output .= $msg . PHP_EOL;
			if (@file_put_contents (self::getFileName(), $output, FILE_APPEND) === false)
			{
				throw new RuntimeException(
					"Can not write into log file with name '" . self::getFileName() . "'"
				);
			}
			
			// output to browser if requested
			$markup = ( ($logLevel == self::LEVEL_ERROR) || ($logLevel == self::LEVEL_WARN) )
				? "background:yellow; color:black;"
				: "";			
			if (self::$mirrorToBrowser)
			{
				echo '<span style="font-family: monospace; font-size:1.1em;' 
					. $markup . '">' . $output . '</span><br>';
			}
			if ($mirror)
			{
				echo '<span style="font-family: monospace; font-size:1.1em;' 
					. $markup . '">[' . self::logLevelIdToName ($logLevel) . '] ' 
					. $msg . '</span><br>';
			}
		}
	}
	
	// returns the name of the current script from trace
	private static function getTraceFilename( $trace )
	{
		$filename = "";
		
		if ( count($trace) > 1)
		{
			$filename = pathinfo($trace[1]['file'], PATHINFO_BASENAME);
		}
		else
		{
			$filename = pathinfo($trace[0]['file'], PATHINFO_BASENAME);
		}
		
		return $filename;
	}
	
	// returns the current function name from trace
	private static function getTraceFunctionName( $trace )
	{
		$functionName = "";
		
		if ( count($trace) >= 3 )
		{
			$functionName = "<";
			if ( isset($trace[2]['class']) )
			{
				$functionName .= $trace[2]['class'] . '::';
			}
			$functionName .= $trace[2]['function'] . "> ";
		}
		
		return $functionName;
	}
	
	// return the name for a log level ID or "undefined" if the log level is invalid
	public static function logLevelIdToName($id)
	{	
		$tags = array (
			self::LEVEL_SYSTEM => "System",
			self::LEVEL_OFF => "Off",
			self::LEVEL_ERROR => "Error",
			self::LEVEL_WARN => "Warn",
			self::LEVEL_INFO => "Info",
			self::LEVEL_DEBUG => "Debug",
			self::LEVEL_TRACE => "Trace"
		);
			
		return array_key_exists($id, $tags) ? $tags[$id] : "undefined";	
	}
	
	// return the log level ID for a log level name, or the default log level if the name is not found
	public static function logLevelNameToId($tag)
	{	
		$ids = array (
			"system" => self::LEVEL_SYSTEM,
			"off" => self::LEVEL_OFF,
			"error" => self::LEVEL_ERROR,
			"warn" => self::LEVEL_WARN,
			"info" => self::LEVEL_INFO,
			"debug" => self::LEVEL_DEBUG,
			"trace" => self::LEVEL_TRACE
		);
			
		return array_key_exists(strtolower($tag), $ids) 
			? $ids[strtolower($tag)] 
			: self::DEFAULT_LOG_LEVEL;	
	}
}
?>