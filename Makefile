.PHONY: help install dev up down build migrate seed fresh logs shell test lint

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-15s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

install: ## Install dependencies and set up the project
	composer install
	npm install
	cp .env.example .env
	php artisan key:generate
	npm run build

dev: ## Start development environment
	docker-compose up -d
	@echo "Application is running at http://localhost"

up: ## Start Docker containers
	docker-compose up -d

down: ## Stop Docker containers
	docker-compose down

build: ## Build Docker containers
	docker-compose build --no-cache

migrate: ## Run database migrations
	php artisan migrate

seed: ## Run database seeders
	php artisan db:seed

fresh: ## Fresh migration with seeding
	php artisan migrate:fresh --seed

logs: ## Show Docker logs
	docker-compose logs -f

shell: ## Access application container shell
	docker-compose exec app bash

lint: ## Run code linting
	./vendor/bin/pint

production-build: ## Build production Docker image
	docker build -f Dockerfile.production -t olx-deal-hunter:latest .

production-up: ## Start production environment
	docker-compose -f docker-compose.production.yml up -d

crawl: ## Run manual crawl command
	php artisan deals:crawl

crawl-dry: ## Run dry-run crawl command
	php artisan deals:crawl --dry-run