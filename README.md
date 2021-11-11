# Micro-blog

## Running local server:

The easiest way to start a local server is to use [symfony](https://symfony.com/download) binary.

* $`composer install`
* $`php bin/console doctrine:database:create`
* $`php bin/console doctrine:schema:create`
* $`symfony server:start`

## Running tests:

* $`composer install`
* $`php bin/console --env=test doctrine:database:create`
* $`php bin/console --env=test doctrine:schema:create`
* $`./vendor/bin/phpunit tests`