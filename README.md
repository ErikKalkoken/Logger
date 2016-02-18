# Logger
This is a basic and easy to use Logger for PHP projects. 

## Installation
You only need to include one file in your project (`Logger.php`) to integrate it. No composer required.

## Usage
Logger can be used within any function or class method without any changes required to the class or other part of the script. 

You just need to initalize the Logger once, e.g. in the initalization section of your main script:
```php
Logger::initialize ("myapplication.log", Logger::LEVEL_INFO);
```

And then you can call the Logger anywhere in your code to create a log message:
```php
Logger::error ("An error occured");
```

## Features
* Logger can be used within any function or class method
* Loglevels: OFF, ERROR, WARN, INFO, DEBUG, TRACE
* Automatic log rotation when maximum file size is reached
* Log messages can be mirrored to the browser (turn on / off / for single log message)
* Tags can be added and removed at runtime to all following log messages (e.g. to identify multipe log messages belonging to the same instance)
* Special log type "system" logs message regardless of current log level

## Example
Here is an example log entry for a simple error message:

`[2016-02-18 19:40:10] [Error] Logger_test.php: Error Entry`


