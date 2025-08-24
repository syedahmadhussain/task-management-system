#!/bin/bash

echo "🧹 Starting complete clean restart..."

# Stop and remove all containers and volumes
echo "🐳 Stopping Docker containers..."
docker-compose down -v

# Remove any existing containers and volumes
echo "🗑️  Removing old containers and volumes..."
docker system prune -f
docker volume prune -f

# Remove any existing database files
echo "🗑️  Cleaning up database files..."
rm -rf /var/lib/docker/volumes/task-management-system_mysql_data

echo "🆕 Starting fresh Docker environment..."
docker-compose up -d

echo "⏳ Waiting for database to be ready..."
sleep 30

echo "🚀 Running fresh migrations..."
docker-compose exec app php artisan migrate:fresh --force

echo "🔄 Clearing all cache..."
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear

echo "✅ Clean restart completed!"
echo ""
echo "📱 Your application is now running at:"
echo "   - API: http://localhost:8000"
echo "   - PhpMyAdmin: http://localhost:8080"
echo ""
echo "📊 Database Info:"
echo "   - Host: localhost:3306"
echo "   - Database: task_management"
echo "   - Username: root"
echo "   - Password: secret"
echo ""
echo "🎯 Next steps:"
echo "   1. Test API: curl http://localhost:8000/api/auth/register"
echo "   2. Check logs: docker-compose logs -f app"
echo "   3. Access container: docker-compose exec app bash"
