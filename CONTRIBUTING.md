# Contributing

```sh
$ git clone git@github.com:mickaelvieira/macaroons.git
$ cd macaroons
$ composer install
```

## Run the test

The test suite has been written with [PHPSpec](http://phpspec.net/)

```sh
$ ./bin/phpspec run --format=pretty
```

## PHP Code Sniffer

This project follows the coding style guide [PSR1](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md) and [PSR2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)

```sh
$ ./bin/phpcs --standard=PSR2 ./src/ --ignore=compatibility.php,functions.php
```
