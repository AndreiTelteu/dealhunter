.PHONY: help install dev up down build migrate seed fresh logs shell lint production-build production-up production-down production-logs crawl crawl-dry backup restore health-check clean

help: ## Show this help message
	@echo 'OLX Deal Hunter - Available Commands'
	@echo '=================================='
	@echo ''
	@echo 'Development Commands:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-20s %s\n", $$1, $$2}' $(MAKEFILE_LIST) | grep -E "(install|dev|up|down|build|migrate|seed|fresh|logs|shell|lint|crawl)"
	@echo ''
	@echo 'Production Commands:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-20s %s\n", $$1, $$2}' $(MAKEFILE_LIST) | grep -E "production"
	@echo ''
	@echo 'Maintenance Commands:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-20s %s\n", $$1, $$2}' $(MAKEFILE_LIST) | grep -E "(backup|restore|health|clean)"

# Development Commands
install: ## Install dependencies and set up the project
	@echo "Installing dependencies..."
	composer install
	npm install
	@if [ ! -f .env ]; then cp .env.example .env; fi
	php artisan key:generate
	npm run build
	@echo "Project setup complete!"

dev: ## Start development environment
	@echo "Starting development environment..."
	docker-compose up -d
	@echo "Waiting for services to be ready..."
	@sleep 10
	@echo "Running migrations..."
	docker-compose exec app php artisan migrate --force
	@echo "Seeding database..."
	docker-compose exec app php artisan db:seed --force
	@echo "Development environment is ready at http://localhost"

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

# Production Commands
production-build: ## Build production Docker image
	@echo "Building production Docker image..."
	docker build -f Dockerfile.production -t olx-deal-hunter:latest .
	@echo "Production image built successfully!"

production-up: ## Start production environment
	@echo "Starting production environment..."
	@if [ ! -f .env.production ]; then echo "Error: .env.production file not found!"; exit 1; fi
	docker-compose -f docker-compose.production.yml --env-file .env.production up -d
	@echo "Waiting for services to be ready..."
	@sleep 30
	@echo "Running production migrations..."
	docker-compose -f docker-compose.production.yml exec app php artisan migrate --force
	@echo "Seeding production database..."
	docker-compose -f docker-compose.production.yml exec app php artisan db:seed --class=ProductionSeeder --force
	@echo "Optimizing application..."
	docker-compose -f docker-compose.production.yml exec app php artisan config:cache
	docker-compose -f docker-compose.production.yml exec app php artisan route:cache
	docker-compose -f docker-compose.production.yml exec app php artisan view:cache
	@echo "Production environment is ready!"

production-down: ## Stop production environment
	docker-compose -f docker-compose.production.yml down

production-logs: ## Show production logs
	docker-compose -f docker-compose.production.yml logs -f

production-shell: ## Access production container shell
	docker-compose -f docker-compose.production.yml exec app sh

production-deploy: ## Full production deployment
	@echo "Starting full production deployment..."
	make production-build
	make production-down
	make production-up
	@echo "Production deployment complete!"

# Crawler Commands
crawl: ## Run manual crawl command
	php artisan deals:crawl

crawl-dry: ## Run dry-run crawl command
	php artisan deals:crawl --dry-run

crawl-production: ## Run crawl in production
	docker-compose -f docker-compose.production.yml exec app php artisan deals:crawl

# Maintenance Commands
backup: ## Create database backup
	@echo "Creating database backup..."
	@mkdir -p backups
	@TIMESTAMP=$$(date +%Y%m%d_%H%M%S); \
	docker-compose exec db pg_dump -U postgres olx_deal_hunter > backups/backup_$$TIMESTAMP.sql
	@echo "Backup created in backups/ directory"

backup-production: ## Create production database backup
	@echo "Creating production database backup..."
	@mkdir -p backups
	@TIMESTAMP=$$(date +%Y%m%d_%H%M%S); \
	docker-compose -f docker-compose.production.yml exec db pg_dump -U postgres olx_deal_hunter > backups/production_backup_$$TIMESTAMP.sql
	@echo "Production backup created in backups/ directory"

restore: ## Restore database from backup (usage: make restore BACKUP=filename)
	@if [ -z "$(BACKUP)" ]; then echo "Usage: make restore BACKUP=filename"; exit 1; fi
	@echo "Restoring database from $(BACKUP)..."
	docker-compose exec db psql -U postgres -d olx_deal_hunter < backups/$(BACKUP)
	@echo "Database restored successfully!"

health-check: ## Check application health
	@echo "Checking application health..."
	@curl -f http://localhost/health || echo "Health check failed!"
	@docker-compose ps

health-check-production: ## Check production application health
	@echo "Checking production application health..."
	@docker-compose -f docker-compose.production.yml ps
	@docker-compose -f docker-compose.production.yml exec app php artisan about

clean: ## Clean up Docker resources
	@echo "Cleaning up Docker resources..."
	docker-compose down -v
	docker system prune -f
	docker volume prune -f
	@echo "Cleanup complete!"

clean-production: ## Clean up production Docker resources
	@echo "Cleaning up production Docker resources..."
	docker-compose -f docker-compose.production.yml down -v
	@echo "Production cleanup complete!"

# Monitoring Commands
monitoring-up: ## Start monitoring stack (Prometheus + Grafana)
	docker-compose -f docker-compose.production.yml --profile monitoring up -d
	@echo "Monitoring stack started:"
	@echo "  Prometheus: http://localhost:9090"
	@echo "  Grafana: http://localhost:3000 (admin/admin)"

monitoring-down: ## Stop monitoring stack
	docker-compose -f docker-compose.production.yml --profile monitoring down

# Log Management
logs-clear: ## Clear application logs
	@echo "Clearing application logs..."
	docker-compose exec app find /var/www/storage/logs -name "*.log" -delete
	@echo "Logs cleared!"

logs-clear-production: ## Clear production logs
	@echo "Clearing production logs..."
	docker-compose -f docker-compose.production.yml exec app find /var/www/storage/logs -name "*.log" -delete
	@echo "Production logs cleared!"