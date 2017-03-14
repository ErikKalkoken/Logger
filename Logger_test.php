<?php

/*
 * Logger_test.php
 * 
 * (C) 2015 Erik Kalkoken
 *
 * Script for testing the Logger class
 *
 *
**/

require_once 'Logger.php';


Class TestClass
{
	public function wantToLog ($area=null)
	{
		Logger::system ("System Entry", $area);
		Logger::error ("Error Entry", $area);
		Logger::warn ("Warning Entry", $area);
		Logger::info ("Info Entry", $area);
		Logger::debug ("Debug Entry", $area);
		Logger::trace ("Trace Entry", $area);
	}
}

function wantToLogToo()
{
	Logger::system ("Function can log too. this is HUGE! ");
}


// testing Logger

Logger::initialize ("Logger_test.log", Logger::LEVEL_DEBUG);
Logger::system ("<================= Log test started");
Logger::system ("--------> Log test started. Logger initialized with log level:" . Logger::getLogLevelName());
Logger::system ("--------> Test 1: Testing all log levels with all message levels");

Logger::setLogLevelByName ("OFF");

Logger::system ("System Entry");
Logger::error ("Error Entry");
Logger::warn ("Warning Entry");
Logger::info ("Info Entry");
Logger::debug ("Debug Entry");
Logger::trace ("Trace Entry");

Logger::setLogLevelByName ("error");

Logger::system ("System Entry");
Logger::error ("Error Entry");
Logger::warn ("Warning Entry");
Logger::info ("Info Entry");
Logger::debug ("Debug Entry");
Logger::trace ("Trace Entry");

Logger::setLogLevelByName ("warn");

Logger::system ("System Entry");
Logger::error ("Error Entry");
Logger::warn ("Warning Entry");
Logger::info ("Info Entry");
Logger::debug ("Debug Entry");
Logger::trace ("Trace Entry");

Logger::setLogLevelByName ("info");

Logger::system ("System Entry");
Logger::error ("Error Entry");
Logger::warn ("Warning Entry");
Logger::info ("Info Entry");
Logger::debug ("Debug Entry");
Logger::trace ("Trace Entry");

Logger::setLogLevelByName ("debug");

Logger::system ("System Entry");
Logger::error ("Error Entry");
Logger::warn ("Warning Entry");
Logger::info ("Info Entry");
Logger::debug ("Debug Entry");
Logger::trace ("Trace Entry");

Logger::setLogLevelByName ("trace");

Logger::system ("System Entry");
Logger::error ("Error Entry");
Logger::warn ("Warning Entry");
Logger::info ("Info Entry");
Logger::debug ("Debug Entry");
Logger::trace ("Trace Entry");

Logger::system ("--------> Test 2: Testing tags and area feature");

Logger::addTag ("Special Forces");
Logger::info ("Somalia action");
Logger::addTag ("UN piece keeping forces");
Logger::info ("Afganistan");
Logger::info ("Afganistan", "North-Area");
Logger::removeTag ("UN piece keeping forces");
Logger::info ("Afganistan", "North-Area");
Logger::removeTag ("Special Forces");

Logger::system ("--------> Test 3: Testing function calls");

$tmp = new TestClass();

Logger::setLogLevelByName ("off");
$tmp->wantToLog();

Logger::setLogLevelByName ("error");
$tmp->wantToLog();

Logger::setLogLevelByName ("warn");
$tmp->wantToLog();

Logger::setLogLevelByName ("info");
$tmp->wantToLog();

Logger::setLogLevelByName ("debug");
$tmp->wantToLog();

Logger::setLogLevelByName ("trace");
$tmp->wantToLog();
wantToLogToo();

Logger::setShowCallerFunction(true);

Logger::setLogLevelByName ("off");
$tmp->wantToLog();

Logger::setLogLevelByName ("error");
$tmp->wantToLog();

Logger::setLogLevelByName ("warn");
$tmp->wantToLog();

Logger::setLogLevelByName ("info");
$tmp->wantToLog();

Logger::setLogLevelByName ("debug");
$tmp->wantToLog();

Logger::setLogLevelByName ("trace");
$tmp->wantToLog();

Logger::setShowCallerFunction(false);
Logger::setLogLevelByName ("info");
$tmp->wantToLog();

Logger::system ("--------> Test 4: Utility functions");

echo Logger::logLevelIdToName (Logger::getLogLevelId()) . "<br>";

$testclass = new TestClass();
$testclass -> wantToLog ();

Logger::system ("--------> Test 5: Tags + Area + Debug logging");
var_dump(Logger::addTag ("Special Forces"));
Logger::setShowCallerFunction(true);

$testclass = new TestClass();
$testclass -> wantToLog ("South Africa");

Logger::system ("Log test finsihed =================>");


?>

