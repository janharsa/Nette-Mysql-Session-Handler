# Mysql Session Handler

Custom MySQL session handler uses Nextras/DBAL and Nette DI 

## Requirements

- [nextras/dbal](https://github.com/nextras/dbal) 3.0+
- [nette/di](https://github.com/nette/di) 2.2+
- PHP 7.2+

## Installation

Use [Composer](http://getcomposer.org/) to install package janharsa/mysql-session-handler:
```sh
$ composer require janharsa/mysql-session-handler:~1.0
```

## Setup
Register an extension in config.neon:

```neon
    extensions:
        sessionHandler: JanHarsa\Session\DI\MysqlSessionHandlerExtension
```
Default table name is 'sessions'. Can be changeg with adding this on the config.local.neon
```neon
sessionHandler:
    tableName: session_in_database
```
