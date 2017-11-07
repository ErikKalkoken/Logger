# Logger
This is a basic and easy to use Logger for PHP projects. I have been using it successfully for all of my web applications.

## Installation
You only need to include one file in your project (`Logger.php`) to integrate it. No composer required.
```php
require_once "Logger.php";`
```

## Usage
Logger can be used within any function or class method without any changes required to the class or other part of the script. 

You just need to initalize the Logger once, e.g. in the initalization section of your main script:
```php
Logger::initialize("myapplication.log", Logger::LEVEL_INFO);
```

And then you can call the Logger anywhere in your code to create a log message:
```php
Logger::error("An error occured");
```

## Features
* Logger can be used within any function or method without having to make any modifications to existing code
* Loglevels: OFF, ERROR, WARN, INFO, DEBUG, TRACE
* Log level can be changed at runtime (e.g. if it needs to be set by URL parameter or from a config file)
* Automatic log rotation when maximum file size is reached
* Log messages can be mirrored to the browser (turn on / off / for single log message)
* Tags can be added and removed at runtime to all following log messages (e.g. to identify multipe log messages belonging to the same instance)
* Special log type "system" logs message regardless of current log level
* add automatically generated unique IDs to all log messages, which makes it easier to identify all log entries belonging to the same request / script execution

## Example
Here is an example log entry for a simple error message:

`[2016-02-18 19:40:10] [Error] Logger_test.php: Error Entry`

## Known Limitations
* Logging in a destructor is not recommended (it may not work)


