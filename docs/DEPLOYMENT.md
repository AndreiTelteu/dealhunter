# Production Deployment Guide

This guide covers the production deployment of the OLX Deal Hunter application using Docker and Docker Compose.

## Prerequisites

- Docker Engine 20.10+
- Docker Compose 2.0+
- At least 2GB RAM and 10GB disk space
- SSL certificates (optional, for HTTPS)

## Quick Start

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd olx-deal-hunter
   ```

2. **Create production environment file**
   ```bash
   cp .env.production.example .env.production
   # Edit .env.production with your configuration
   ```

3. **Deploy using the deployment script**
   ```bash
   ./scripts/deploy.sh production
   ```

4. **Verify deployment**
   ```bash
   curl http://localhost/health
   ```

## Manual Deployment

### 1. Environment Configuration

Create `.env.production` based on `.env.production.example`:

```bash
cp .env.production.example .env.production
```

**Required Configuration:**
- `APP_KEY`: Generate with `php artisan key:generate --show`
- `DB_PASSWORD`: Secure PostgreSQL password
- `REDIS_PASSWORD`: Secure Redis password
- `APP_URL`: Your domain URL
- `MCP_PLAYWRIGHT_TOKEN`: MCP service token
- `AI_API_KEY`: OpenAI or other AI provider API key

### 2. Build and Deploy

```bash
# Build production image
make production-build

# Start production environment
make production-up

# Or use Docker Compose directly
docker-compose -f docker-compose.production.yml --env-file .env.production up -d
```

### 3. Initialize Application

```bash
# Run migrations
docker-compose -f docker-compose.production.yml exec app php artisan migrate --force

# Seed demo data
docker-compose -f docker-compose.production.yml exec app php artisan db:seed --class=ProductionSeeder --force

# Optimize application
docker-compose -f docker-compose.production.yml exec app php artisan config:cache
docker-compose -f docker-compose.production.yml exec app php artisan route:cache
docker-compose -f docker-compose.production.yml exec app php artisan view:cache
```

## Production Architecture

### Services

- **app**: Laravel application with PHP-FPM and Nginx
- **db**: PostgreSQL 15 database
- **redis**: Redis cache and session store
- **prometheus** (optional): Metrics collection
- **grafana** (optional): Monitoring dashboard

### Security Features

- Read-only containers where possible
- Security headers in Nginx
- Rate limiting and security monitoring
- Encrypted sessions and secure cookies
- Database authentication with SCRAM-SHA-256

### Performance Optimizations

- OPcache with preloading
- Redis for caching and sessions
- Gzip compression
- Static asset caching
- Database query optimization
- Connection pooling

## Monitoring

### Health Checks

```bash
# Application health
curl http://localhost/health

# Simple ping
curl http://localhost/ping

# Detailed monitoring
./scripts/monitor.sh health
```

### Logs

```bash
# Application logs
make production-logs

# Specific service logs
docker-compose -f docker-compose.production.yml logs app
docker-compose -f docker-compose.production.yml logs db
docker-compose -f docker-compose.production.yml logs redis

# Monitor logs in real-time
./scripts/monitor.sh logs
```

### Metrics (Optional)

Start monitoring stack:
```bash
make monitoring-up
```

Access dashboards:
- Prometheus: http://localhost:9090
- Grafana: http://localhost:3000 (admin/admin)

## Maintenance

### Database Backups

```bash
# Create backup
make backup-production

# Restore from backup
make restore BACKUP=production_backup_20240101_120000.sql
```

### Updates

```bash
# Full redeployment
make production-deploy
```

### Log Management

```bash
# Clear old logs
make logs-clear-production

# View log statistics
./scripts/monitor.sh stats
```

## SSL/HTTPS Setup

### Using Let's Encrypt

1. Install Certbot:
   ```bash
   sudo apt-get install certbot
   ```

2. Generate certificates:
   ```bash
   sudo certbot certonly --standalone -d your-domain.com
   ```

3. Update Nginx configuration to use SSL certificates

4. Set up automatic renewal:
   ```bash
   sudo crontab -e
   # Add: 0 12 * * * /usr/bin/certbot renew --quiet
   ```

### TLS Termination

The production container serves HTTP on port 80 only. Terminate TLS with a reverse proxy or load balancer and configure `APP_URL` with the public HTTPS URL. This repository does not provide a self-signed certificate generator.

## Troubleshooting

### Common Issues

1. **Container won't start**
   ```bash
   # Check logs
   docker-compose -f docker-compose.production.yml logs app
   
   # Check container status
   docker-compose -f docker-compose.production.yml ps
   ```

2. **Database connection issues**
   ```bash
   # Check database logs
   docker-compose -f docker-compose.production.yml logs db
   
   # Test connection
   docker-compose -f docker-compose.production.yml exec app php artisan tinker
   # In tinker: DB::connection()->getPdo();
   ```

3. **Performance issues**
   ```bash
   # Check resource usage
   ./scripts/monitor.sh check
   
   # Optimize application
   docker-compose -f docker-compose.production.yml exec app php artisan optimize
   ```

### Log Locations

- Application logs: `/var/www/storage/logs/`
- Nginx logs: `/var/log/nginx/`
- PHP-FPM logs: `/var/log/php-fpm/`
- Supervisor logs: `/var/log/supervisor/`

### Performance Tuning

1. **Database optimization**
   - Adjust PostgreSQL configuration in `docker/postgres/postgresql.conf`
   - Monitor slow queries
   - Add indexes for frequently queried columns

2. **PHP optimization**
   - Tune OPcache settings in `docker/php/production.ini`
   - Adjust memory limits
   - Monitor PHP-FPM pool settings

3. **Redis optimization**
   - Configure memory limits in `docker/redis/redis.conf`
   - Monitor cache hit rates
   - Adjust eviction policies

## Security Considerations

1. **Environment Variables**
   - Never commit `.env.production` to version control
   - Use strong passwords for all services
   - Rotate API keys regularly

2. **Network Security**
   - Use firewall to restrict access
   - Consider using a reverse proxy
   - Enable fail2ban for SSH protection

3. **Container Security**
   - Regularly update base images
   - Scan images for vulnerabilities
   - Use non-root users where possible

4. **Application Security**
   - Keep Laravel and dependencies updated
   - Monitor security logs
   - Implement proper input validation

## Scaling

### Horizontal Scaling

1. **Load Balancer Setup**
   - Use Nginx or HAProxy as load balancer
   - Configure session affinity or shared sessions
   - Health check endpoints

2. **Database Scaling**
   - Read replicas for read-heavy workloads
   - Connection pooling with PgBouncer
   - Database sharding for large datasets

3. **Cache Scaling**
   - Redis Cluster for high availability
   - Separate cache instances by purpose
   - CDN for static assets

### Vertical Scaling

1. **Resource Allocation**
   - Increase container memory limits
   - Add more CPU cores
   - Use faster storage (SSD)

2. **Configuration Tuning**
   - Optimize database settings
   - Tune PHP-FPM worker processes
   - Adjust OPcache memory allocation

## Support

For issues and questions:
1. Check the troubleshooting section
2. Review application logs
3. Check the project documentation
4. Create an issue in the repository
