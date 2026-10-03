.DEFAULT_GOAL := help

.PHONY: install
install: ## Install the dependencies
	composer install --no-interaction --prefer-dist

.PHONY: test-laravel
test-laravel: ## Test the Laravel adapter, after scripts/install-laravel.sh 12
	vendor/bin/phpunit --configuration=phpunit.laravel.xml

.PHONY: test-symfony
test-symfony: ## Test the Symfony adapter, after scripts/install-symfony.sh 7.4
	vendor/bin/phpunit --configuration=phpunit.symfony.xml

.PHONY: data
data: ## Replace data/ with a release of the builder (VERSION=4.0.0, the latest by default)
	scripts/update-data.sh $(VERSION)

-include vendor/stanislas-poisson/php-dev-tools/Makefile.inc
