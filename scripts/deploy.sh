#!/bin/bash

# OLX Deal Hunter Production Deployment Script
# Usage: ./scripts/deploy.sh [environment]

set -e

ENVIRONMENT=${1:-production}
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

echo "🚀 Starting OLX Deal Hunter deployment for $ENVIRONMENT environment..."

# Check if required files exist
if [ ! -f "$PROJECT_DIR/.env.$ENVIRONMENT" ]; then
    echo "❌ Error: .env.$ENVIRONMENT file not found!"
    echo "Please create .env.$ENVIRONMENT based on .env.$ENVIRONMENT.example"
    exit 1
fi

# Load environment variables
source "$PROJECT_DIR/.env.$ENVIRONMENT"

echo "📋 Pre-deployment checks..."

# Check Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Error: Docker is not running!"
    exit 1
fi

# Check required environment variables
required_vars=("APP_KEY" "DB_PASSWORD" "REDIS_PASSWORD")
for var in "${required_vars[@]}"; do
    if [ -z "${!var}" ]; then
        echo "❌ Error: $var is not set in .env.$ENVIRONMENT"
        exit 1
    fi
done

echo "✅ Pre-deployment checks passed"

# Create backup directory
mkdir -p "$PROJECT_DIR/backups"

# Backup database if production environment exists
if docker-compose -f docker-compose.production.yml ps db | grep -q "Up"; then
    echo "💾 Creating database backup..."
    TIMESTAMP=$(date +%Y%m%d_%H%M%S)
    docker-compose -f docker-compose.production.yml exec -T db pg_dump -U postgres olx_deal_hunter > "backups/pre_deploy_backup_$TIMESTAMP.sql"
    echo "✅ Database backup created: backups/pre_deploy_backup_$TIMESTAMP.sql"
fi

# Build production image
echo "🔨 Building production Docker image..."
docker build -f Dockerfile.production -t olx-deal-hunter:latest .
echo "✅ Production image built successfully"

# Stop existing containers
echo "🛑 Stopping existing containers..."
docker-compose -f docker-compose.production.yml down

# Start new containers
echo "🚀 Starting new containers..."
docker-compose -f docker-compose.production.yml --env-file .env.$ENVIRONMENT up -d

# Wait for services to be ready
echo "⏳ Waiting for services to be ready..."
sleep 30

# Check if services are healthy
echo "🔍 Checking service health..."
for i in {1..10}; do
    if docker-compose -f docker-compose.production.yml exec -T app curl -f http://localhost/health > /dev/null 2>&1; then
        echo "✅ Application is healthy"
        break
    fi
    
    if [ $i -eq 10 ]; then
        echo "❌ Application health check failed after 10 attempts"
        echo "📋 Container status:"
        docker-compose -f docker-compose.production.yml ps
        echo "📋 Application logs:"
        docker-compose -f docker-compose.production.yml logs app --tail=50
        exit 1
    fi
    
    echo "⏳ Waiting for application to be ready... (attempt $i/10)"
    sleep 10
done

# Run database migrations
echo "🗄️ Running database migrations..."
docker-compose -f docker-compose.production.yml exec -T app php artisan migrate --force

# Seed production database
echo "🌱 Seeding production database..."
docker-compose -f docker-compose.production.yml exec -T app php artisan db:seed --class=ProductionSeeder --force

# Optimize application
echo "⚡ Optimizing application..."
docker-compose -f docker-compose.production.yml exec -T app php artisan config:cache
docker-compose -f docker-compose.production.yml exec -T app php artisan route:cache
docker-compose -f docker-compose.production.yml exec -T app php artisan view:cache

# Clear old logs
echo "🧹 Cleaning up old logs..."
docker-compose -f docker-compose.production.yml exec -T app find /var/www/storage/logs -name "*.log" -mtime +30 -delete || true

# Final health check
echo "🏥 Final health check..."
if docker-compose -f docker-compose.production.yml exec -T app curl -f http://localhost/health > /dev/null 2>&1; then
    echo "✅ Deployment successful!"
    echo ""
    echo "🎉 OLX Deal Hunter is now running in $ENVIRONMENT mode"
    echo "📊 Application URL: $APP_URL"
    echo "🔍 Health check: $APP_URL/health"
    echo ""
    echo "📋 Useful commands:"
    echo "  View logs: make production-logs"
    echo "  Access shell: make production-shell"
    echo "  Run crawler: make crawl-production"
    echo "  Monitor: docker-compose -f docker-compose.production.yml ps"
else
    echo "❌ Final health check failed!"
    echo "📋 Container status:"
    docker-compose -f docker-compose.production.yml ps
    exit 1
fi