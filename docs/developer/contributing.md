# Contributing to LWT

This guide is mainly aimed at developers, but it can give useful insights on how LWT is structured, which could help with debugging. The first step you need to take is to clone LWT from the official GitHub repository ([HugoFara/lwt](https://github.com/HugoFara/lwt)).

## Get Composer

Getting [Composer](https://getcomposer.org/download/) is required if you want to edit LWT on the server side, but it will also be useful to edit JS and CSS code, so it is *highly recommended*. Composer is a lightweight dependency manager that *does not need* a server to run.

Once Composer is ready, go to the lwt folder (most likely ``lwt/``), and type

```bash
composer install --dev
```

This will automatically download all the required dependencies.

## Create and Edit Themes

Themes are stored at ``src/themes/``. If you want to create a new theme, simply add it to a subfolder. You can also edit existing themes.

To apply the changes you made to a theme, run

```bash
composer minify
```

This command will minify all CSS and JS.

Alternatively, you can run

```bash
php -r "require 'src/php/minifier.php'; minifyAllCSS();"
```

 It minifies only CSS.

### Debug your theme

You may not want to see your code minified, so you can use

```bash
composer no-minify
```

It has the same effect as copying the folder ``src/themes/`` to ``themes/``. WARNING: It can break your relative paths!

### Add Images to your Themes

We support a smart minifying system: relative paths are automatically adapted to point to the previous location *while minifying only*.
As a consequence:

* You can use images from ``css/images/`` in your theme.
  * If your theme is under ``src/themes/mytheme/``, you should use the path ``../../../css/theimage``.
* You can add your own files under your custom theme folder.
  * Hence, the path should look like ``./myimage``.

When debugging your theme, files are simply copied to the ``themes/`` folder, which can break the path to files in ``css/``.

### My theme does not contain all the Skinning Files

That's not a problem at all. When LWT looks for a file that should be contained in ``src/themes/{{The Theme}}/``, it checks if the file exists. If not, it goes to ``css/`` and tries to get the same file. With this system, your themes **do not need to have the same files as ``src/css/``**.

## Change JS behavior

As with themes, LWT minifies JS code for a better user experience. Please refer to the previous section for detailed explanations; this section will only go through import points.

### Edit JS code

Clear code is stored at ``src/js/``. Once again, the *actual* code used by LWT should be at ``js/``. After you have done any modification, either run ``composer minify`` or ``php -r "require 'src/php/minifier.php'; minifyAllJS();"``.

### Debug JS code

To copy code in a non-obfuscated form, run ``composer no-minify`` or replace the content of ``js/`` with ``src/js/``.

## Edit PHP code

The PHP codebase is not yet well structured, but here is a general organization:

* Pages rendered to the client are under the root folder (``do_text.php``, ``do_test.php``, etc...)
* Files that should not be rendered directly are under the ``inc/`` ("include") folder.
* Other files useful for *development only* are under ``src/php/``.

### Testing your Code

It is highly advised to test your code. Tests should be wrote under ``tests/``. We use PHP Unit for testing.

To run all tests:

 ``composer test``

Alternatively:

 ``./vendor/bin/phpunit``

#### Code Coverage Report

To generate a code coverage report (requires Xdebug):

```bash
XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-text
```

This will generate an HTML report in `coverage-report/index.html` and display a text summary in the terminal. Open the HTML report in your browser for detailed line-by-line coverage analysis.

### Security Check

We use Psalm to find code flaws and inconsistencies. Use ``./vendor/bin/psalm``.

You can configure the reporting level in ``psalm.xml``.

### Advice: Follow the Code Style Standards

Nobody likes to debug unreadable code. A good way to avoid thinking about it is to include phpcs directly in your IDE. You can also download it and run it regularly on your code.

You can run it through composer. Use ``php ./vendor/bin/squizlabs/phpcs.phar [filename]`` to see style violations on a file. You can fix them using

```bash
php ./vendor/bin/squizlabs/phpcbf.phar [filename]
```

## Interact with and modify the REST API

Starting from 2.9.0-fork, LWT provides a RESTful API. The main handler for the API is `api.php`.
You can find a more exhaustive API documentation at [api.md](./api.md).

If you plan to develop the API, please follow the RESTful standards.
To debug:

1. Install Node and NPM.
2. Run `npm install` in the main LWT folder.
3. Run `npm test` to test the API.

## Improving Documentation

To regenerate all PHP and Markdown documentation, use ``composer doc``.
For the JS documentation, you need NPM. Use `./node_modules/.bin/jsdoc -c jsdoc.json`.

### General Documentation

The documentation is split across Markdown (``.md``) files in ``docs/``.
Then, those files are requested by ``info.php``.
The final version is ``info.html``, which contains all files.

To regenerate ``info.hml``, run ``composer info.html``.

### PHP Code Documentation

Code documentation (everything under `docs/html/` and `docs/php/`) is automatically generated.
If you see an error, the PHP code is most likely at fault.
However, don't hesitate to signal the issue.

Currently, the PHP documentation is generated two times:

* With [Doxygen](https://www.doxygen.nl/index.html) (run ``doxygen Doxyfile`` to regenerate it),
it generates documentation for MarkDown and PHP files. It will be removed in LWT 3.0.0.
* Using [phpDocumentor](https://phpdoc.org/). phpDoc generates PHP documentation and is the preferred way to do so.
You can use it through `php tools/phpDocumentor` if installed with [Phive](https://phar.io/).

### JS Code Documentation

Code documentation for JavaScript is available at `docs/js/` is is generated thourgh [JSDoc](https://jsdoc.app/).
The JSDoc configuration file is `jsdoc.json`.

## New version

LWT-fork follows a strict procedure for new versions.
This section is mainly intended for the maintainers, but feel free to take a peak at it.

The steps to publish a new version are:

1. In the [CHANGELOG](./CHANGELOG.md), add the latest release number and date.
2. In `inc/kernel_utility.php`, update `LWT_APP_VERSION` and `LWT_RELEASE_DATE`.
3. Update `PROJECT_NUMBER` in `Doxyfile` to the latest release number.
4. Regenerate documentation with `composer doc`.
5. Commit your changes, `git commit -m "Regenerates documentation for release []."`
6. Add a version tag with annotation `git tag -a [release number]` and push the changes.
7. If all the GitHub actions are successfull, write a new release on GitHub linking to the previously created tag.
8. The new version is live!

## Other Ways of Contribution

### Drop a star on GitHub

This is an open-source project. It means that anyone can contribute, but nobody gets paid for improving it. Dropping a star, leaving a comment, or posting an issue is *essential* because the only reward developers get from time spent on LWT is the opportunity to discuss with users.

### Spread the Word

LWT is a non-profitable piece of software, so we won't have much time or money to advertise it. If you enjoy LWT and want to see it grow, share it!

### Discuss

Either go to the forum of the [official LWT version](https://sourceforge.net/p/learning-with-texts/discussion/), or come and [discuss on the community version](https://github.com/HugoFara/lwt/discussions).

### Support on OpenCollective

LWT is hosted on OpenCollective, you can support the development of the app at <https://opencollective.com/lwt-community>.

Thanks for your interest in contributing!
