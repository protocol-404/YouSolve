# Setup Instructions for YouCode Evaluator Backend

This document provides detailed instructions for setting up and running the YouCode Evaluator backend.

## System Requirements

- PHP 8.1 or higher
- Composer 2.0 or higher
- PostgreSQL 14 or higher
- Docker and Docker Compose (optional, for containerized setup)
- Git

## Installation Steps

### Option 1: Local Setup

1. **Clone the repository**

```bash
git clone https://github.com/youcode/evaluator.git
cd evaluator
```

2. **Install PHP dependencies**

```bash
composer install
```

3. **Configure environment variables**

```bash
cp .env.example .env
php artisan key:generate
```

4. **Edit the .env file with your database credentials**

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=youcode_evaluator
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

5. **Create the database**

```bash
# Using psql
psql -U postgres
CREATE DATABASE youcode_evaluator;
\q
```

6. **Run migrations and seed the database**

```bash
php artisan migrate
php artisan db:seed --class=RoleSeeder
```

7. **Start the development server**

```bash
php artisan serve
```

The API will be available at `http://localhost:8000/api`.

### Option 2: Docker Setup

1. **Clone the repository**

```bash
git clone https://github.com/youcode/evaluator.git
cd evaluator
```

2. **Configure environment variables**

```bash
cp .env.example .env
```

3. **Edit the .env file with your database credentials**

```
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=youcode_evaluator
DB_USERNAME=postgres
DB_PASSWORD=postgres
```

4. **Build and start the Docker containers**

```bash
docker-compose up -d
```

5. **Install PHP dependencies**

```bash
docker-compose exec app composer install
```

6. **Generate application key**

```bash
docker-compose exec app php artisan key:generate
```

7. **Run migrations and seed the database**

```bash
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed --class=RoleSeeder
```

The API will be available at `http://localhost:8000/api`.

## Project Structure

```
youcode-evaluator/
├── app/
│   ├── Http/
│   │   ├── Controllers/       # API controllers
│   │   ├── Middleware/        # Custom middleware
│   │   └── Kernel.php         # Middleware registration
│   └── Models/                # Eloquent models
├── database/
│   ├── migrations/            # Database migrations
│   └── seeders/               # Database seeders
├── routes/
│   └── api.php                # API routes
├── tests/
│   └── Feature/               # Feature tests
├── docker/                    # Docker configuration
│   ├── Dockerfile             # PHP container configuration
│   └── nginx/                 # Nginx configuration
├── docker-compose.yml         # Docker Compose configuration
└── docs/                      # Documentation
    ├── api.md                 # API documentation
    ├── database.md            # Database schema documentation
    ├── authentication.md      # Authentication flow documentation
    └── setup.md               # Setup instructions (this file)
```

## Available API Endpoints

The API endpoints are documented in detail in the [API Documentation](api.md). Here's a summary of the available endpoints:

- **Authentication**
  - `POST /api/register` - Register a new user
  - `POST /api/login` - Login a user
  - `POST /api/logout` - Logout a user
  - `POST /api/forgot-password` - Request password reset
  - `POST /api/reset-password` - Reset password

- **User Management**
  - `GET /api/users` - List users
  - `POST /api/users` - Create a user
  - `GET /api/users/{id}` - Get user details
  - `PUT /api/users/{id}` - Update a user
  - `DELETE /api/users/{id}` - Delete a user
  - `GET /api/users/{id}/progress` - Get user progress
  - `GET /api/my-progress` - Get current user progress

- **Evaluations**
  - `GET /api/evaluations` - List evaluations
  - `POST /api/evaluations` - Create an evaluation
  - `GET /api/evaluations/{id}` - Get evaluation details
  - `PUT /api/evaluations/{id}` - Update an evaluation
  - `DELETE /api/evaluations/{id}` - Delete an evaluation

- **Problems**
  - `GET /api/problems` - List problems
  - `POST /api/problems` - Create a problem
  - `GET /api/problems/{id}` - Get problem details
  - `PUT /api/problems/{id}` - Update a problem
  - `DELETE /api/problems/{id}` - Delete a problem

- **Submissions**
  - `GET /api/submissions` - List submissions
  - `POST /api/submissions` - Create a submission
  - `GET /api/submissions/{id}` - Get submission details
  - `PUT /api/submissions/{id}` - Update a submission
  - `DELETE /api/submissions/{id}` - Delete a submission

- **Results**
  - `GET /api/results` - List results
  - `POST /api/results` - Create a result
  - `GET /api/results/{id}` - Get result details
  - `PUT /api/results/{id}` - Update a result
  - `DELETE /api/results/{id}` - Delete a result

## Testing

To run the tests:

```bash
# Local setup
php artisan test

# Docker setup
docker-compose exec app php artisan test
```

## Default Roles

The system comes with three predefined roles:

1. **candidate** - Applicants to YouCode training
2. **instructor** - Teaching staff for evaluation
3. **administrator** - Platform management personnel

## Creating an Admin User

To create an administrator user:

```bash
# Local setup
php artisan tinker
```

```php
$adminRole = App\Models\Role::where('name', 'administrator')->first();
$user = new App\Models\User();
$user->name = 'Admin User';
$user->email = 'admin@example.com';
$user->password = Hash::make('your_password');
$user->role_id = $adminRole->id;
$user->save();
exit;
```

```bash
# Docker setup
docker-compose exec app php artisan tinker
```

Then run the same PHP code as above.

## Troubleshooting

### Database Connection Issues

If you encounter database connection issues:

1. Check that PostgreSQL is running
2. Verify your database credentials in the `.env` file
3. Ensure the database exists
4. Check that the database user has appropriate permissions

### Docker Issues

If you encounter issues with Docker:

1. Ensure Docker and Docker Compose are installed and running
2. Check container logs: `docker-compose logs`
3. Restart containers: `docker-compose down && docker-compose up -d`

### Permission Issues

If you encounter permission issues:

1. Ensure storage and bootstrap/cache directories are writable:
   ```bash
   chmod -R 775 storage bootstrap/cache
   ```

2. If using Docker, fix permissions inside the container:
   ```bash
   docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
   ```

## Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
- [Docker Documentation](https://docs.docker.com/)
- [Laravel Sanctum Documentation](https://laravel.com/docs/sanctum)
