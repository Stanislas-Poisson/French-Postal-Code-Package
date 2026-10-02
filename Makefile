.DEFAULT_GOAL := help

.PHONY: help
help: ## List the commands
	@grep -hE '^[a-zA-Z_-]+:.*## ' $(MAKEFILE_LIST) | awk -F':.*## ' '{printf "  %-14s %s\n", $$1, $$2}'

.PHONY: install
install: ## Install the dependencies
	composer install --no-interaction --prefer-dist

.PHONY: test
test: ## Run the tests of the reader and the loader
	vendor/bin/phpunit

.PHONY: test-laravel
test-laravel: ## Test the Laravel adapter, after scripts/install-laravel.sh 12
	vendor/bin/phpunit --configuration=phpunit.laravel.xml

.PHONY: test-symfony
test-symfony: ## Test the Symfony adapter, after scripts/install-symfony.sh 7.4
	vendor/bin/phpunit --configuration=phpunit.symfony.xml

.PHONY: coverage
coverage: ## Run the tests with the coverage of src/
	vendor/bin/phpunit --coverage-text

.PHONY: quality
quality: ## Check the code style and run the static analysis
	vendor/bin/pint --test
	vendor/bin/phpstan analyse --memory-limit=1G --no-progress

.PHONY: quality-fix
quality-fix: ## Fix the code style
	vendor/bin/pint

.PHONY: data
data: ## Replace data/ with a release of the builder (VERSION=4.0.0, the latest by default)
	scripts/update-data.sh $(VERSION)
