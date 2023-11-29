[![Build Status](https://travis-ci.org/agencychief/php-couchdb.svg?branch=master)](https://travis-ci.org/agencychief/php-couchdb)

# PHP CouchDB

:warning: This project is under early and active development.  Comments, experimentation and feedback all gratefully received.

A lightweight library to make it very easy to work with [CouchDB](http://couchdb.apache.org/) from PHP.  Uses [Guzzle](http://docs.guzzlephp.org/en/stable/), requires PHP 7+.

## Installation

It's recommended to install this library using [Composer](https://getcomposer.org/):

```
composer require agencychief/php-couchdb
```

## Usage

Here's the tl;dr of how to begin.  For more detailed examples, see the [wiki](https://github.com/agencychief/php-couchdb/wiki) and/or generate the API docs with `composer docs`

```php
<?php

require "vendor/autoload.php";

// connect to CouchDB (does make a call to check we can connect)
$server = new \PHPCouchDB\Server(["url" => "http://localhost:5984"]);

// get a list of databases; each one is a \PHPCouchDB\Database object
$databases = $server->getAllDbs();

// work with the "test" database (also a \PHPCouchDB\Database object)
$test_db = $server->useDb(["name" => "test", "create_if_not_exists" => true]);

// add a document - you may specify the "id" here if you like
$doc = $test_db->create(["name" => "Alice", "interests" => ["eating", "wondering"]]);

// inspect the document
print_r($doc);

// update the document - a NEW document is returned by this operation, showing the server representation of the document
$doc->friends[] = "Cheshire Cat";
$updated_doc = $doc->update();

// done?  Delete the doc
$updated_doc->delete();
```

For more examples, conflict handling and really everything else, see more detailed documentation on the wiki.

## For Developers

Contributions and issues are all _very_ welcome on this project - and of course we'd love to hear how you're using the library in your own projects.

For information about working with the project locally or opening pull requests, see [[CONTRIBUTING]].
