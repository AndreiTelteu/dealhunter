#!/bin/bash

# OLX Deal Hunter Monitoring Script
# Usage: ./scripts/monitor.sh [check|logs|stats|health]

set -e

COMMAND=${1:-check}
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

case $COMMAND in
    "check")
        echo "🔍 System Health Check"
        echo "====================="
        
        # Check container status
        echo "📦 Container Status:"
        docker-compose -f docker-compose.production.yml ps
        echo ""
        
        # Check application health
        echo "🏥 Application Health:"
        if curl -s http://localhost/health | jq . 2>/dev/null; then
            echo "✅ Health endpoint accessible"
        else
            echo "❌ Health endpoint not accessible"
        fi
        echo ""
        
        # Check disk usage
        echo "💾 Disk Usage:"
        df -h | grep -E "(Filesystem|/dev/)"
        echo ""
        
        # Check memory usage
        echo "🧠 Memory Usage:"
        free -h
        echo ""
        
        # Check Docker resources
        echo "🐳 Docker Resources:"
        docker system df
        ;;
        
    "logs")
        echo "📋 Recent Application Logs"
        echo "========================="
        docker-compose -f docker-compose.production.yml logs --tail=100 app
        ;;
        
    "stats")
        echo "📊 System Statistics"
        echo "==================="
        
        # Database statistics
        echo "🗄️ Database Statistics:"
        docker-compose -f docker-compose.production.yml exec -T db psql -U postgres -d olx_deal_hunter -c "
            SELECT 
                schemaname,
                tablename,
                n_tup_ins as inserts,
                n_tup_upd as updates,
                n_tup_del as deletes,
                n_live_tup as live_rows
            FROM pg_stat_user_tables 
            ORDER BY n_live_tup DESC;
        " 2>/dev/null || echo "❌ Could not fetch database statistics"
        echo ""
        
        # Application statistics
        echo "📈 Application Statistics:"
        docker-compose -f docker-compose.production.yml exec -T app php artisan about --only=environment,cache,database 2>/dev/null || echo "❌ Could not fetch application statistics"
        ;;
        
    "health")
        echo "🏥 Detailed Health Check"
        echo "======================="
        
        # Application health
        echo "🔍 Application Health:"
        curl -s http://localhost/health | jq . 2>/dev/null || echo "❌ Health check failed"
        echo ""
        
        # Database connectivity
        echo "🗄️ Database Connectivity:"
        if docker-compose -f docker-compose.production.yml exec -T db pg_isready -U postgres -d olx_deal_hunter >/dev/null 2>&1; then
            echo "✅ Database is ready"
        else
            echo "❌ Database is not ready"
        fi
        
        # Redis connectivity
        echo "🔴 Redis Connectivity:"
        if docker-compose -f docker-compose.production.yml exec -T redis redis-cli ping >/dev/null 2>&1; then
            echo "✅ Redis is ready"
        else
            echo "❌ Redis is not ready"
        fi
        
        # Check recent errors
        echo ""
        echo "🚨 Recent Errors (last 24 hours):"
        docker-compose -f docker-compose.production.yml exec -T app find /var/www/storage/logs -name "*.log" -mtime -1 -exec grep -l "ERROR\|CRITICAL\|EMERGENCY" {} \; 2>/dev/null | head -5 || echo "No recent error logs found"
        ;;
        
    *)
        echo "Usage: $0 [check|logs|stats|health]"
        echo ""
        echo "Commands:"
        echo "  check  - Basic system health check"
        echo "  logs   - Show recent application logs"
        echo "  stats  - Show system and application statistics"
        echo "  health - Detailed health check"
        exit 1
        ;;
esac