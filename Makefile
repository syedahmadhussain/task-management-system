.PHONY: help build up down restart logs install migrate seed fresh test clean prod

# Default target
help:
	@echo "Task Management System - Available Commands:"
	@echo ""
	@echo "Setup Commands:"
	@echo "  make install    - Install and setup the project"
	@echo "  make build      - Build Docker containers"
	@echo "  make up         - Start all services"
	@echo "  make down       - Stop all services"
	@echo "  make restart    - Restart all services"
	@echo ""
	@echo "Database Commands:"
	@echo "  make migrate    - Run database migrations"
	@echo "  make seed       - Seed database with sample data"
	@echo "  make fresh      - Fresh database with migrations and seed"
	@echo ""
	@echo "Development Commands:"
	@echo "  make logs       - Show container logs"
	@echo "  make test       - Run tests"
	@echo "  make clean      - Clean up containers and volumes"
	@echo ""
	@echo "Production Commands:"
	@echo "  make prod       - Setup for production"

# Installation and setup
install:
	@echo "🚀 Installing Task Management System..."
	cp .env.example .env
	docker-compose build
	docker-compose up -d
	@echo "⏳ Waiting for containers to start..."
	sleep 10
	docker-compose exec app composer install --no-dev --optimize-autoloader
	docker-compose exec app php artisan key:generate
	docker-compose exec app php artisan jwt:secret
	docker-compose exec app php artisan migrate --force
	docker-compose exec app php artisan db:seed --force
	docker-compose exec app php artisan config:cache
	docker-compose exec app php artisan route:cache
	docker-compose exec app php artisan view:cache
	@echo "✅ Installation complete!"
	@echo "📱 Application: http://localhost:8000"
	@echo "🗄️  PhpMyAdmin: http://localhost:8080"
	@echo "🔌 WebSocket: ws://localhost:6001"

# Docker commands
build:
	@echo "🏗️  Building containers..."
	docker-compose build

up:
	@echo "🚀 Starting services..."
	docker-compose up -d
	@echo "✅ Services started!"
	@echo "📱 Application: http://localhost:8000"
	@echo "🗄️  PhpMyAdmin: http://localhost:8080"

down:
	@echo "🛑 Stopping services..."
	docker-compose down

restart:
	@echo "🔄 Restarting services..."
	docker-compose restart
	@echo "✅ Services restarted!"

# Database commands
migrate:
	@echo "📊 Running migrations..."
	docker-compose exec app php artisan migrate --force

seed:
	@echo "🌱 Seeding database..."
	docker-compose exec app php artisan db:seed --force

fresh:
	@echo "🗑️  Fresh database setup..."
	docker-compose exec app php artisan migrate:fresh --seed --force
	@echo "✅ Database refreshed!"

# Development commands
logs:
	@echo "📋 Container logs:"
	docker-compose logs -f

test:
	@echo "🧪 Running tests..."
	docker-compose exec app php artisan test

clean:
	@echo "🗑️  Cleaning up..."
	docker-compose down -v
	docker system prune -f
	@echo "✅ Cleanup complete!"

# Production setup
prod:
	@echo "🏭 Setting up for production..."
	docker-compose exec app php artisan config:cache
	docker-compose exec app php artisan route:cache
	docker-compose exec app php artisan view:cache
	docker-compose exec app php artisan optimize
	@echo "✅ Production optimization complete!"
