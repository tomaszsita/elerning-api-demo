.PHONY: help install test phpcs phpcbf php-cs-fixer phpstan build up down

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-15s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

install: ## Install dependencies
	docker-compose exec app composer install

test: ## Run tests
	docker-compose exec app composer test

test-coverage: ## Run tests with coverage
	docker-compose exec app composer test:coverage

phpcs: ## Run PHP Code Sniffer
	docker-compose exec app vendor/bin/phpcs --standard=phpcs.xml

phpcbf: ## Run PHP Code Beautifier and Fixer
	docker-compose exec app vendor/bin/phpcbf --standard=phpcs.xml

php-cs-fixer: ## Run PHP CS Fixer
	docker-compose exec app vendor/bin/php-cs-fixer fix

php-cs-fixer-check: ## Run PHP CS Fixer in dry-run mode
	docker-compose exec app vendor/bin/php-cs-fixer fix --dry-run --diff

phpstan: ## Run PHPStan
	docker-compose exec app composer phpstan

phpstan-baseline: ## Generate PHPStan baseline
	docker-compose exec app composer phpstan:baseline

code-quality: ## Run all code quality tools
	@echo "Running all code quality tools..."
	@make phpcs
	@make php-cs-fixer-check
	@make phpstan

fix: ## Fix all code style issues
	@echo "Fixing code style issues..."
	@make phpcbf
	@make php-cs-fixer

build: ## Build Docker containers
	docker-compose build

up: ## Start Docker containers
	docker-compose up -d

down: ## Stop Docker containers
	docker-compose down

logs: ## Show Docker logs
	docker-compose logs -f
