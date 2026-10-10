#!/usr/bin/env bash
# Adds what is needed to test the Laravel adapter against one major version of Laravel.
#
# Usage: scripts/install-laravel.sh 12|13
#
# The frameworks are not in composer.json: Laravel and Symfony do not share compatible versions of their components,
# so each job of the CI installs only its own. Locally, this changes composer.json and the vendor directory; restore
# them afterwards with `git checkout composer.json && composer install`.
set -euo pipefail

case "${1:-}" in
    12) TESTBENCH='^10.0' ;;
    13) TESTBENCH='^11.0' ;;
    *)
        echo "Usage: $0 12|13" >&2
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

composer require --dev --no-interaction --no-update "orchestra/testbench:$TESTBENCH" "larastan/larastan:^3.0"
composer update --no-interaction --prefer-dist
