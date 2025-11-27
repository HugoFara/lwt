# Contributing to LWT

This guide is mainly aimed at developers, but it can give useful insights on how LWT is structured, which could help with debugging. The first step you need to take is to clone LWT from the official GitHub repository ([HugoFara/lwt](https://github.com/HugoFara/lwt)).

## Prerequisites

### Composer (PHP)

Getting [Composer](https://getcomposer.org/download/) is required for PHP development (testing, static analysis). Go to the lwt folder and run:

```bash
composer install --dev
```

### Node.js and npm (Frontend)

[Node.js](https://nodejs.org/) is required for frontend development (JavaScript/TypeScript, CSS). Install dependencies with:

```bash
npm install
```

This is required for building frontend assets, running the dev server, and type checking.

## Create and Edit Themes

Themes are stored at `src/frontend/themes/`. If you want to create a new theme, simply add it to a subfolder. You can also edit existing themes.

To build themes, run:

```bash
npm run build:themes
```

This minifies CSS files and copies assets to `assets/themes/`.

### Add Images to your Themes

You can include images in your theme:

* Use images from `assets/css/images/` with path `../../../assets/css/images/theimage.png`
* Add your own files to your theme folder and reference with `./myimage.png`

### My theme does not contain all the Skinning Files

That's not a problem at all. When LWT looks for a file that should be contained in `assets/themes/{{The Theme}}/`, it checks if the file exists. If not, it falls back to `assets/css/` and tries to get the same file. With this system, your themes **do not need to have all the same files as `src/frontend/css/`**.

## Frontend Development (JavaScript/TypeScript)

LWT uses TypeScript for frontend code, built with Vite.

### Source Files

TypeScript source files are in `src/frontend/js/`:

* `main.ts` - Vite entry point
* `pgm.ts` - Core utilities
* `jq_pgm.ts` - jQuery-dependent functions
* `text_events.ts` - Text reading interface
* `audio_controller.ts` - Audio playback
* And more...

### Development Workflow

1. Start the dev server with Hot Module Replacement:

   ```bash
   npm run dev
   ```

2. Check TypeScript types:

   ```bash
   npm run typecheck
   ```

3. Build for production:

   ```bash
   npm run build
   ```

### Build Commands

| Command | Description |
|---------|-------------|
| `npm run dev` | Start Vite dev server with HMR |
| `npm run build` | Build Vite JS/CSS bundles |
| `npm run build:themes` | Build theme CSS files |
| `npm run build:all` | Build everything (Vite + themes) |
| `npm run typecheck` | Run TypeScript type checking |
| `composer build` | Alias for `npm run build:all` |

## Edit PHP code

The PHP codebase structure:

* `index.php` - Front controller entry point
* `src/backend/Controllers/` - MVC Controllers
* `src/backend/Core/` - Core modules (database, utilities, AJAX handlers)
* `src/backend/Legacy/` - Legacy PHP files being migrated

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

To test the API:

```bash
npm test
```

## End-to-End Testing (Cypress)

LWT uses [Cypress](https://www.cypress.io/) for end-to-end testing. E2E tests verify that the application works correctly from a user's perspective.

### Running E2E Tests

Make sure the development server is running on `http://localhost:8000`, then:

```bash
npm run e2e          # Run all E2E tests headlessly
npx cypress open     # Open Cypress interactive UI
```

### Test Structure

E2E tests are located in `cypress/e2e/` and cover:

* `01-setup.cy.ts` - Database setup and demo installation
* `02-home.cy.ts` - Home page and navigation
* `03-legacy-redirects.cy.ts` - Legacy URL redirects
* `04-languages.cy.ts` - Language management
* `05-texts.cy.ts` - Text management
* `06-words.cy.ts` - Word/term management
* `07-admin.cy.ts` - Admin pages (settings, statistics, backup)
* `08-api.cy.ts` - REST API endpoints

Test fixtures are in `cypress/fixtures/test-data.json`.

### Writing E2E Tests

When adding new features or fixing bugs that affect user-facing functionality, consider adding or updating E2E tests:

1. Create or modify test files in `cypress/e2e/`
2. Use fixtures for test data
3. Run tests locally before submitting PRs

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
2. In `src/backend/Core/version.php`, update `LWT_APP_VERSION` and `LWT_RELEASE_DATE`.
3. Update `PROJECT_NUMBER` in `Doxyfile` to the latest release number.
4. Build frontend assets with `npm run build:all`.
5. Regenerate documentation with `composer doc`.
6. Commit your changes, `git commit -m "Regenerates documentation for release []."`
7. Add a version tag with annotation `git tag -a [release number]` and push the changes.
8. If all the GitHub actions are successful, write a new release on GitHub linking to the previously created tag.
9. The new version is live!

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
