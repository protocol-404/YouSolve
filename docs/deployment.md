# Deployment Guide - YouCode Evaluator

This document provides detailed instructions for deploying the YouCode Evaluator platform in various environments.

## Deployment Options

The YouCode Evaluator platform can be deployed using several methods:

1. **Docker Deployment** - Recommended for production environments
2. **Manual Deployment** - For environments without Docker support
3. **Development Deployment** - For local development and testing

## Prerequisites

### Docker Deployment
- Docker Engine (version 20.10.0 or higher)
- Docker Compose (version 1.29.0 or higher)
- 2GB RAM minimum (4GB recommended)
- 10GB disk space minimum

### Manual Deployment
- PHP 8.1 or higher
- Composer 2.0 or higher
- PostgreSQL 14 or higher
- Nginx or Apache web server
- Node.js 16 or higher (for frontend assets)
- 2GB RAM minimum (4GB recommended)
- 10GB disk space minimum

## Docker Deployment

### Step 1: Clone the Repository

```bash
git clone https://github.com/youcode/evaluator.git
cd evaluator
```

### Step 2: Configure Environment Variables

```bash
cp .env.example .env
```

Edit the `.env` file to set the following variables:

```
APP_NAME=YouCodeEvaluator
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://youcode-evaluator.example.com

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=youcode_evaluator
DB_USERNAME=postgres
DB_PASSWORD=postgres

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database
```

Generate the application key:

```bash
docker-compose run --rm app php artisan key:generate
```

### Step 3: Build and Start Containers

```bash
docker-compose build
docker-compose up -d
```

### Step 4: Run Migrations and Seeders

```bash
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed --class=RoleSeeder
```

### Step 5: Create Admin User

```bash
docker-compose exec app php artisan tinker
```

```php
$adminRole = App\Models\Role::where('name', 'administrator')->first();
$user = new App\Models\User();
$user->name = 'Admin User';
$user->email = 'admin@example.com';
$user->password = Hash::make('your_secure_password');
$user->role_id = $adminRole->id;
$user->save();
exit;
```

### Step 6: Configure Nginx (Optional)

If you want to use a custom domain, configure your Nginx server to proxy requests to the Docker container:

```nginx
server {
    listen 80;
    server_name youcode-evaluator.example.com;
    
    location / {
        proxy_pass http://localhost:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

### Step 7: Set Up SSL (Recommended)

For production environments, it's recommended to set up SSL using Let's Encrypt:

```bash
sudo apt-get install certbot python3-certbot-nginx
sudo certbot --nginx -d youcode-evaluator.example.com
```

## Manual Deployment

### Step 1: Clone the Repository

```bash
git clone https://github.com/youcode/evaluator.git
cd evaluator
```

### Step 2: Install Dependencies

```bash
composer install --optimize-autoloader --no-dev
npm install
npm run build
```

### Step 3: Configure Environment Variables

```bash
cp .env.example .env
```

Edit the `.env` file to set the appropriate variables for your environment.

Generate the application key:

```bash
php artisan key:generate
```

### Step 4: Set Up Database

Create a PostgreSQL database and user:

```bash
sudo -u postgres psql
CREATE DATABASE youcode_evaluator;
CREATE USER youcode WITH PASSWORD 'your_secure_password';
GRANT ALL PRIVILEGES ON DATABASE youcode_evaluator TO youcode;
\q
```

Update the `.env` file with your database credentials:

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=youcode_evaluator
DB_USERNAME=youcode
DB_PASSWORD=your_secure_password
```

### Step 5: Run Migrations and Seeders

```bash
php artisan migrate
php artisan db:seed --class=RoleSeeder
```

### Step 6: Configure Web Server

#### Nginx Configuration

```nginx
server {
    listen 80;
    server_name youcode-evaluator.example.com;
    root /path/to/evaluator/public;
    
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    
    index index.php;
    
    charset utf-8;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }
    
    error_page 404 /index.php;
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

#### Apache Configuration

Create a `.htaccess` file in the public directory:

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>
    
    RewriteEngine On
    
    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
    
    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]
    
    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

### Step 7: Set Up SSL (Recommended)

For production environments, it's recommended to set up SSL using Let's Encrypt:

```bash
sudo apt-get install certbot python3-certbot-nginx
sudo certbot --nginx -d youcode-evaluator.example.com
```

### Step 8: Set Permissions

```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Step 9: Configure Cron Job for Scheduled Tasks

```bash
crontab -e
```

Add the following line:

```
* * * * * cd /path/to/evaluator && php artisan schedule:run >> /dev/null 2>&1
```

### Step 10: Set Up Queue Worker (Optional)

For background processing of code submissions, set up a queue worker:

```bash
sudo nano /etc/systemd/system/youcode-queue.service
```

Add the following content:

```
[Unit]
Description=YouCode Evaluator Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /path/to/evaluator/artisan queue:work --sleep=3 --tries=3

[Install]
WantedBy=multi-user.target
```

Enable and start the service:

```bash
sudo systemctl enable youcode-queue.service
sudo systemctl start youcode-queue.service
```

## Development Deployment

### Step 1: Clone the Repository

```bash
git clone https://github.com/youcode/evaluator.git
cd evaluator
```

### Step 2: Install Dependencies

```bash
composer install
npm install
npm run dev
```

### Step 3: Configure Environment Variables

```bash
cp .env.example .env
```

Edit the `.env` file to set the appropriate variables for your development environment.

Generate the application key:

```bash
php artisan key:generate
```

### Step 4: Set Up Database

Create a PostgreSQL database for development:

```bash
sudo -u postgres psql
CREATE DATABASE youcode_evaluator_dev;
CREATE USER youcode_dev WITH PASSWORD 'dev_password';
GRANT ALL PRIVILEGES ON DATABASE youcode_evaluator_dev TO youcode_dev;
\q
```

Update the `.env` file with your database credentials:

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=youcode_evaluator_dev
DB_USERNAME=youcode_dev
DB_PASSWORD=dev_password
```

### Step 5: Run Migrations and Seeders

```bash
php artisan migrate
php artisan db:seed --class=RoleSeeder
```

### Step 6: Start Development Server

```bash
php artisan serve
```

The development server will be available at `http://localhost:8000`.

## Troubleshooting

### Docker Deployment Issues

#### Container Fails to Start

Check the container logs:

```bash
docker-compose logs app
```

Common issues:
- Database connection errors: Check the DB_* environment variables
- Permission issues: Check the ownership of storage and bootstrap/cache directories
- Port conflicts: Change the exposed port in docker-compose.yml

#### Database Connection Issues

Ensure the database container is running:

```bash
docker-compose ps
```

If the database container is not running, check its logs:

```bash
docker-compose logs db
```

### Manual Deployment Issues

#### 500 Server Error

Check the Laravel logs:

```bash
tail -f storage/logs/laravel.log
```

Common issues:
- Permission problems: Ensure storage and bootstrap/cache directories are writable
- Missing dependencies: Run `composer install` again
- Environment configuration: Check the .env file for correct settings

#### Database Connection Issues

Verify PostgreSQL is running:

```bash
sudo systemctl status postgresql
```

Check the database connection settings in the .env file.

#### Web Server Issues

Check the web server error logs:

```bash
# For Nginx
tail -f /var/log/nginx/error.log

# For Apache
tail -f /var/log/apache2/error.log
```

## Maintenance

### Backup

#### Database Backup

```bash
# Docker deployment
docker-compose exec db pg_dump -U postgres youcode_evaluator > backup.sql

# Manual deployment
pg_dump -U youcode youcode_evaluator > backup.sql
```

#### Application Backup

```bash
# Backup the entire application directory
tar -czf youcode-evaluator-backup.tar.gz /path/to/evaluator
```

### Updates

#### Docker Deployment

```bash
git pull
docker-compose build
docker-compose up -d
docker-compose exec app php artisan migrate
```

#### Manual Deployment

```bash
git pull
composer install --optimize-autoloader --no-dev
npm install
npm run build
php artisan migrate
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Security Considerations

1. **Environment Variables**: Never commit the `.env` file to version control
2. **API Tokens**: Regularly rotate API tokens
3. **SSL**: Always use HTTPS in production
4. **Firewall**: Configure a firewall to restrict access to necessary ports
5. **Updates**: Regularly update dependencies to patch security vulnerabilities
6. **Backups**: Regularly backup the database and application files
7. **Monitoring**: Set up monitoring for the application and server

## Performance Optimization

1. **Caching**: Enable caching in production
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

2. **Queue**: Use queue workers for code execution
   ```bash
   php artisan queue:work
   ```

3. **Database Indexing**: Ensure proper indexes are in place for frequently queried columns

4. **Load Balancing**: For high-traffic deployments, consider setting up load balancing with multiple application servers

## Scaling

### Horizontal Scaling

For high-traffic deployments, you can scale the application horizontally:

1. Set up multiple application servers
2. Use a load balancer to distribute traffic
3. Use a shared file system for storage
4. Configure session management to use Redis or database

### Vertical Scaling

For moderate traffic increases:

1. Increase server resources (CPU, RAM)
2. Optimize database queries
3. Implement caching strategies

## Conclusion

This deployment guide covers the basic steps to deploy the YouCode Evaluator platform in various environments. For specific deployment scenarios or advanced configurations, please refer to the Laravel documentation or contact the development team.
