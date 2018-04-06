Contributions and issues are all _very_ welcome on this project - and of course we'd love to hear how you're using the library in your own projects.

Before offering pull requests, check that you can run the tests, generate the documentation, and that the syntax checks pass (instructions for all of these below ...) as Travis will run these on your patch.  Pull requests will _require_ tests and documentation before they get merged, but feel free to open a pull request and ask for help.

### Running Tests

This project has [PHPUnit](https://phpunit.de/) tests.  To run them: `composer test`

### Generating Documentation

This project uses [PHPDox](http://phpdox.de/) to generate API documentation.  To generate the docs: `composer docs` and the output will be in the `docs/` directory.

### Syntax Checks

The syntax checker is [PHP Code Sniffer](https://github.com/squizlabs/PHP_CodeSniffer) and you can run this with `composer phpcs`

