# kwf-exception-logger-raven
Log exceptions for sentry with raven client

## Installation
Extend config.ini in your koala-framework application

```
debug.exceptionLogger = KwfExceptionLoggerRaven_Kwf_Exception_Logger_Raven
ravenPhp.dsn = url-to-sentry-project
ravenJs.dsn = url-to-sentry-project
```
