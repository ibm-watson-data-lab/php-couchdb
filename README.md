# PHP CouchDB

:warning: This project is under early and active development.  Comments, experimentation and feedback all gratefully received.

A lightweight library to make it very easy to work with [CouchDB](http://couchdb.apache.org/) from PHP.  Uses [Guzzle](http://docs.guzzlephp.org/en/stable/), requires PHP 7+.

## Installation

It's recommended to install this library using [Composer](https://getcomposer.org/):

```
composer require ibm-watson-data-lab/php-couchdb:dev-master
```

## Usage

A simple use case to check you can connect to CouchDB and check what version of CouchDB it's running.

```php
<?php

require "vendor/autoload.php";

$server = new \PHPCouchDB\Server(["url" => "http://localhost:5984"]);
echo $server->getVersion();
```

## For Developers

Contributions and issues are all _very_ welcome on this project - and of course we'd love to hear how you're using the library in your own projects.

Before offering pull requests, check that you can run the tests, generate the documentation, and that the syntax checks pass (instructions for all of these below ...) as Travis will run these on your patch.  Pull requests will _require_ tests and documentation before they get merged, but feel free to open a pull request and ask for help.

### Running Tests

This project has [PHPUnit](https://phpunit.de/) tests.  To run them: `composer test`

### Generating Documentation

This project uses [PHPDox](http://phpdox.de/) to generate API documentation.  To generate the docs: `composer docs` and the output will be in the `docs/` directory.

### Syntax Checks

The syntax checker is [PHP Code Sniffer](https://github.com/squizlabs/PHP_CodeSniffer) adn you can run this with `composer phpcs`
