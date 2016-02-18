<?php

/*
 * Logger.php
 *
 * Copyright(C) 2015 Erik Kalkoken
 *
 * Class for handling logging in a php script. 
 * Implemented as static class to enable easy usage without the need for dependency injection or use of global variables
 *
 * HISTORY:
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
 * Logger::initialize ("myapplication.log", Logger::LEVEL_INFO);	// Initialized the logger, should be called once
 * Logger::error ("An error occured");	// Outputs a message on ERROR level to the log
 * 
**/


class Logger
{
	const LEVEL_SYSTEM = 1;	// for log messages that always need to be shown, but are not an error
	const LEVEL_OFF = 2;	
	const LEVEL_ERROR = 3;
	const LEVEL_WARN = 4;
	const LEVEL_INFO = 5;
	const LEVEL_DEBUG = 6;
	const LEVEL_TRACE = 7;
		
	const LOGGER_DATEFORMAT = "Y-m-d H:i:s";
	const MAX_FILESIZE = 51200;	// in kilobytes, default is 50 MB
	
	static private $logLevel = self::LEVEL_INFO;
	static private $fileName = "logfile.log";
	static private $initialized = false;
	static private $tags = null;		// optional tag that will added to every log message
	static private $activated = true;
	static private $showCallerFunction = false;	// dont usually show function names
	static private $max_filesize = self::MAX_FILESIZE;
	static private $mirrorToBrowser = false;
	
	// Pseudo contructor
	
	static public function initialize ($fileName, $logLevel = self::LEVEL_INFO, $max_filesize=null)
	{
		self::$fileName = getcwd () . "/" . $fileName;
		self::$logLevel = $logLevel;
		self::$initialized = true;
		if (isset($max_filesize)) self::$max_filesize = $max_filesize;
		
		// rotate file if too big
		$size = intval (@filesize (self::$fileName) / 1024);
		
		if ( ($size !== false) && ($size > self::$max_filesize) )
		{
			// new name is old name + timestamp + .log
			$path = pathinfo(self::$fileName, PATHINFO_DIRNAME);			
			$path = ($path == ".") ? "" : str_replace('\\', '/', $path) . "/";		// don't use current directory for path, also need to convert backslashes to make it system agnostic
			$newname = $path . pathinfo(self::$fileName, PATHINFO_FILENAME) . "_" . date ("YmdHis") . ".log";
		
			if (@rename (self::$fileName, $newname) === true)
			{
				self::system ("Previous logfile had exceeded the size limit of " . self::$max_filesize ." KB and has been renamed to '" . $newname ."'");
			}
			
		}
	}
	
	// getter and setter
	static public function getFileName () {	return self::$fileName;	}	
	static public function getInitialised () {	return self::$initialized;	}
	static public function getMirrorToBrowser () {	return self::$mirrorToBrowser;	}
	static public function setMirrorToBrowser ($value) { self::$mirrorToBrowser = $value; }
	static public function getLogLevelId () { return self::$logLevel; }
	static public function getLogLevelName () {	return self::logLevelIdToName(self::$logLevel);	}
	static public function setLogLevelByName ($name) { return self::setLogLevel (self::logLevelNameToId ($name)); }
	static public function setLogLevel ($logLevel)
	{
		$success = false;
		
		switch ($logLevel)
		{
			case self::LEVEL_OFF:
			case self::LEVEL_ERROR:
			case self::LEVEL_WARN:
			case self::LEVEL_INFO:
			case self::LEVEL_DEBUG:
			case self::LEVEL_TRACE:
				self::$logLevel = $logLevel;
				$success = true;
				break;
			
			default:
				$success = false;
				break;
		}
		
		self::system ("Loglevel set to " . self::logLevelIdToName($logLevel));
		
		return $success;	
	}

	static public function getTags () {	return self::$tags; }
	static public function addTag ($tag) { self::$tags[] = $tag; }
	static public function removeTag ($tag)
	{
		$id = array_search ($tag, self::$tags);
		
		if($id !== false) unset (self::$tags[$id]);
	}
	
	static public function setShowCallerFunction($value)
	{	
		self::system ("Show caller functions is set to: " . var_export ($value, true));
		self::$showCallerFunction = filter_var($value, FILTER_VALIDATE_BOOLEAN);
	}
	
	// methods
	static public function system ($msg, $area=null, $mirror=null)	{ self::log ($msg, self::LEVEL_SYSTEM, $area, $mirror); }	
	static public function error ($msg, $area=null, $mirror=null) 	{ self::log ($msg, self::LEVEL_ERROR, $area, $mirror); }	
	static public function warn ($msg, $area=null, $mirror=null) 	{ self::log ($msg, self::LEVEL_WARN, $area, $mirror); }	
	static public function info ($msg, $area=null, $mirror=null) 	{ self::log ($msg, self::LEVEL_INFO, $area, $mirror); }	
	static public function debug ($msg, $area=null, $mirror=null)	{ self::log ($msg, self::LEVEL_DEBUG, $area, $mirror); }	
	static public function trace ($msg, $area=null, $mirror=null) 	{ self::log ($msg, self::LEVEL_TRACE, $area, $mirror); }
	
	static private function log ($msg, $logLevel, $area, $mirror)
	{
		if (self::$activated && (self::$logLevel >= $logLevel) )
		{		
			// just for debug
			/*
			$trace = debug_backtrace();			
			echo '<b>' . $msg . '</b><br>';
			var_dump ($trace);	
			echo '<br>';
			*/
			
			$output = "[" . date(self::LOGGER_DATEFORMAT) . "] ";
			$output .= "[" . self::logLevelIdToName ($logLevel) . "] ";
			
			if (isset (self::$tags))
			{
				foreach (self::$tags as $tag) $output .= "{" . $tag . "} ";
			}	
			
			$trace = debug_backtrace();
			$output .= self::getTraceFilename ($trace) . ": ";
			
			// Function names will ony be shown when loglevel is set to debug or trace or show caller function is set to true
			if ( ( (self::$logLevel == self::LEVEL_DEBUG) || (self::$logLevel == self::LEVEL_TRACE) ) || (self::$showCallerFunction === true) )
			{
				$output .= self::getTraceFunctionName ($trace);		
			}
		
			if (isset ($area))
			{
				$output .= "(" . $area . ") ";
			}	
			
			$output .= $msg . PHP_EOL;
			if ( @file_put_contents (self::$fileName, $output, FILE_APPEND) === false)
			{
				trigger_error ("Can not write into log file with name '" . self::$fileName . "'", E_USER_ERROR);
			}
			
			// output to browser if requested
			if ( ($logLevel == self::LEVEL_ERROR) || ($logLevel == self::LEVEL_WARN) ) echo '<span style="background:yellow; color:black">';
			if (self::$mirrorToBrowser) echo '<span style="font-family: monospace; font-size:1.1em">' . $output . '</span><br>';
			if ($mirror) echo '<span style="font-family: monospace; font-size:1.1em">[' . self::logLevelIdToName ($logLevel) . '] '. $msg . '</span><br>';
			if ( ($logLevel == self::LEVEL_ERROR) || ($logLevel == self::LEVEL_WARN) ) echo '</span>';
		}
	}
	
	static private function getTraceFilename ($trace)
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
	
	static public function getSupportedLogLevels()
	{
		$list = array();
		for ($i=self::LEVEL_ERROR; $i<=self::LEVEL_TRACE; $i++)
		{
			$list[]=self::logLevelIdToName($i);
		}
		return $list;
	}
	
	static private function getTraceFunctionName ($trace)
	{
		
		$functionName = "";
		
		if ( count ($trace) >= 3 )
		{
			$functionName = "<";
			if ( isset ($trace[2]['class']) )
			{
				$functionName .= $trace[2]['class'] . '::';
			}
			$functionName .= $trace[2]['function'] . "> ";
		}
		
		return $functionName;
	}
	
	static public function turnOff () { self::$activated = false; }
	static public function turnOn ()  { self::$activated = true;	}
	
	static public function logLevelIdToName ($id)
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
			
		return array_key_exists ($id, $tags) ? $tags[$id] : "undefined";	
	}
	
	static public function logLevelNameToId ($tag)
	{	
		$ids = array (
			"System" => self::LEVEL_SYSTEM,
			"Off" => self::LEVEL_OFF,
			"Error" => self::LEVEL_ERROR,
			"Warn" => self::LEVEL_WARN,
			"Info" => self::LEVEL_INFO,
			"Debug" => self::LEVEL_DEBUG,
			"Trace" => self::LEVEL_TRACE
		);
			
		return array_key_exists ($tag, $ids) ? $ids[$tag] : self::LEVEL_INFO;	
	}
}
?>