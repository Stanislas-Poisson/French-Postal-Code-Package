#!/usr/bin/env bash
# Adds what is needed to test the Symfony adapter against one version of Symfony.
#
# Usage: scripts/install-symfony.sh 6.4|7.4|8.0
#
# The frameworks are not in composer.json: Laravel and Symfony do not share compatible versions of their components,
# so each job of the CI installs only its own. Locally, this changes composer.json and the vendor directory; restore
# them afterwards with `git checkout composer.json && composer install`.
set -euo pipefail

case "${1:-}" in
    6.4) SYMFONY='6.4.*'; BUNDLE='^2.13' ;;
    7.4) SYMFONY='7.4.*'; BUNDLE='^2.13' ;;
    8.0) SYMFONY='8.0.*'; BUNDLE='^3.0' ;;
    *)
        echo "Usage: $0 6.4|7.4|8.0" >&2
        exit 1
        ;;
esac

# The quality tools are not part of the dependencies of a framework job: their requirements must not limit the
# frameworks that the package supports. These jobs keep what they use: PHPStan with its extensions, and PHPUnit.
composer remove --dev --no-interaction --no-update stanislas-poisson/php-dev-tools
composer require --dev --no-interaction --no-update \
    "phpstan/phpstan:^2.1" \
    "phpstan/phpstan-phpunit:^2.0" \
    "phpstan/phpstan-strict-rules:^2.0"

composer require --dev --no-interaction --no-update \
    "symfony/framework-bundle:$SYMFONY" \
    "symfony/console:$SYMFONY" \
    "doctrine/orm:^3.0" \
    "doctrine/dbal:^4.0" \
    "doctrine/doctrine-bundle:$BUNDLE" \
    "symfony/var-exporter:$SYMFONY" \
    "phpstan/phpstan-doctrine:^2.0"
composer update --no-interaction --prefer-dist
