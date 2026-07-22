#!/usr/bin/env bash

set -e

echo "🚀 Starting OmniRoute v2 Production Deployment..."

# 1. Pull latest code from repository
echo "📥 Pulling latest git commit from main branch..."
git pull origin main

# 2. Build and spin up production Docker containers
echo "🐳 Building and starting production Docker containers..."
docker compose -f docker-compose.prod.yml up -d --build

# 3. Execute central database migrations
echo "🗄️ Running central database migrations..."
docker compose -f docker-compose.prod.yml exec -T app php artisan migrate --force

# 4. Execute tenant database migrations across all tenant schemas
echo "🏢 Running tenant database migrations across schemas..."
docker compose -f docker-compose.prod.yml exec -T app php artisan tenants:migrate --force

# 5. Optimize & cache application configuration, routes, and views
echo "⚡ Caching production configuration and routes..."
docker compose -f docker-compose.prod.yml exec -T app php artisan config:cache
docker compose -f docker-compose.prod.yml exec -T app php artisan route:cache
docker compose -f docker-compose.prod.yml exec -T app php artisan view:cache
docker compose -f docker-compose.prod.yml exec -T app php artisan event:cache

echo "✅ Production Deployment completed successfully!"
