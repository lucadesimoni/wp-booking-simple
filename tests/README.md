# Tests

Two layers of tests ship with the plugin.

## 1. Standalone tests (no dependencies)

Fast checks for the pure booking logic plus a boot smoke-test that loads the
whole plugin against lightweight WordPress stubs and asserts that shortcodes,
blocks and AJAX handlers register without fatal errors.

```bash
php tests/standalone/run.php
```

Exits non-zero on failure, so it is suitable for CI without a database.

## 2. WordPress integration tests (PHPUnit)

`tests/phpunit/` contains `WP_UnitTestCase` tests covering the database layer,
availability logic, magic-link page provisioning and email sending.

Provide the WordPress test library and run PHPUnit. The simplest setup uses
[`wp-env`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/):

```bash
npm -g i @wordpress/env
wp-env start
wp-env run tests-cli --env-cwd=wp-content/plugins/wp-booking-simple phpunit
```

Or, with a manual test-lib install:

```bash
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
WP_TESTS_DIR=/tmp/wordpress-tests-lib vendor/bin/phpunit
```

Configuration lives in `phpunit.xml.dist`; the bootstrap is `tests/bootstrap.php`.
