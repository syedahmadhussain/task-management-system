# Task Management System

A comprehensive task management system built with Laravel backend and React frontend, featuring real-time updates via WebSocket.

## Features

- 🔐 **Authentication & Authorization**: JWT-based auth with role-based access control
- 👥 **Multi-role Support**: Admin, Manager, and Member roles with different permissions
- 📋 **Task Management**: Create, assign, and track tasks with priorities and due dates
- 📊 **Project Management**: Organize tasks within projects with team collaboration
- 📈 **Dashboard & Analytics**: Real-time overview with statistics and charts
- 🔄 **Real-time Updates**: Live notifications via WebSocket (Laravel Reverb)
- 📅 **AI-powered Scheduling**: Intelligent task scheduling based on priority and deadlines
- 📱 **Responsive Design**: Works seamlessly on desktop and mobile devices

## Tech Stack

**Backend:**
- Laravel 11
- MySQL 8.0
- Laravel Reverb (WebSocket)
- JWT Authentication
- Docker & Docker Compose

**Frontend:**
- React 18
- TypeScript
- Tailwind CSS
- React Query (TanStack)
- Laravel Echo

## Quick Start

### Prerequisites

- Docker & Docker Compose
- Make (optional, for easier commands)

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd task-management-system
   ```

2. **Start the application**
   ```bash
   make install
   ```
   
   Or manually:
   ```bash
   cp .env.example .env
   docker-compose up -d
   docker-compose exec app composer install
   docker-compose exec app php artisan key:generate
   docker-compose exec app php artisan jwt:secret
   docker-compose exec app php artisan migrate --seed
   ```

3. **Access the application**
   - **Backend API**: http://localhost:8000
   - **Frontend**: http://localhost:5173 (if running separately)
   - **Database Admin**: http://localhost:8080 (PhpMyAdmin)
   - **WebSocket**: ws://localhost:6001

## Available Commands

```bash
make help          # Show all available commands
make install       # Install and setup the project
make up            # Start all services
make down          # Stop all services
make restart       # Restart all services
make migrate       # Run database migrations
make seed          # Seed database with sample data
make fresh         # Fresh database with migrations and seed
make logs          # Show container logs
make test          # Run tests
make clean         # Clean up containers and volumes
make prod          # Setup for production
```

## Default Users

After seeding, you can log in with these accounts:

**Admin:**
- Email: `admin@taskmanagement.com`
- Password: `password`

**Manager:**
- Email: `manager@taskmanagement.com`
- Password: `password`

**Member:**
- Email: `member@taskmanagement.com`
- Password: `password`

## API Documentation

### Authentication Endpoints
- `POST /api/auth/login` - User login
- `POST /api/auth/register` - User registration (admin only)
- `POST /api/auth/logout` - User logout
- `GET /api/auth/me` - Get current user info

### Task Endpoints
- `GET /api/tasks` - List tasks
- `POST /api/tasks` - Create task (admin/manager)
- `GET /api/tasks/{id}` - Get task details
- `PUT /api/tasks/{id}` - Update task
- `DELETE /api/tasks/{id}` - Delete task (admin/manager)
- `PATCH /api/tasks/{id}/complete` - Mark task as complete

### Project Endpoints
- `GET /api/projects` - List projects
- `POST /api/projects` - Create project (admin/manager)
- `GET /api/projects/{id}` - Get project details
- `PUT /api/projects/{id}` - Update project
- `DELETE /api/projects/{id}` - Delete project (admin/manager)

### Schedule Endpoints
- `GET /api/my-schedule` - Get personal task schedule
- `GET /api/tasks/schedule` - Get team/organization schedule (admin/manager)

## Real-time Features

The system includes real-time updates using Laravel Reverb (WebSocket):

- **Live Dashboard Updates**: New tasks and updates appear instantly
- **Task Status Changes**: Real-time notifications when tasks are updated
- **Multi-user Collaboration**: See changes made by other team members live
- **Connection Status**: Visual indicator showing WebSocket connection status

## Architecture

### Backend Structure
```
app/
├── Http/Controllers/Api/    # API Controllers
├── Http/Requests/          # Form Request Validators
├── Http/Middleware/        # Custom Middleware
├── Models/                 # Eloquent Models
├── Services/               # Business Logic Services
├── Events/                 # WebSocket Events
└── Enums/                  # Enums for constants
```

### Database Schema
- **Users**: User management with roles and organizations
- **Organizations**: Multi-tenant organization support
- **Projects**: Project management with managers
- **Tasks**: Task tracking with assignments and scheduling
- **Relationships**: Proper foreign key relationships

## Development

### Running Tests
```bash
make test
```

### Viewing Logs
```bash
make logs
```

### Database Operations
```bash
make migrate        # Run migrations
make seed          # Seed database
make fresh         # Fresh database setup
```

## Production Deployment

1. **Setup for production**
   ```bash
   make prod
   ```

2. **Environment Configuration**
   - Update `.env` with production values
   - Set `APP_ENV=production` and `APP_DEBUG=false`
   - Configure proper database credentials
   - Set up SSL/TLS for WebSocket connections

3. **Security Considerations**
   - Change default JWT secret
   - Update database passwords
   - Configure firewall rules
   - Set up proper SSL certificates
   - Enable rate limiting

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

For support, email support@taskmanagement.com or create an issue in the repository.
