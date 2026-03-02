# 🚀 Production Deployment Guide - SkulSoft School Management System

**Version:** 1.0  
**Last Updated:** February 28, 2026  
**Laravel Version:** 11.x  
**PHP Version:** 8.2+

---

## Table of Contents

1. [Pre-Deployment Checklist](#pre-deployment-checklist)
2. [Security Hardening](#security-hardening)
3. [Environment Configuration](#environment-configuration)
4. [Database Optimization](#database-optimization)
5. [Performance Optimization](#performance-optimization)
6. [File & Storage Setup](#file-storage-setup)
7. [Queue & Cron Configuration](#queue-cron-configuration)
8. [SSL & Domain Setup](#ssl-domain-setup)
9. [Monitoring & Logging](#monitoring-logging)
10. [Backup Strategy](#backup-strategy)
11. [Deployment Steps](#deployment-steps)
12. [Post-Deployment Testing](#post-deployment-testing)
13. [Troubleshooting](#troubleshooting)

---

## Pre-Deployment Checklist

### Required Software
- [ ] **PHP 8.2+** with extensions:
  - BCMath
  - Ctype
  - Fileinfo
  - JSON
  - Mbstring
  - OpenSSL
  - PDO
  - Tokenizer
  - XML
  - GD or Imagick
  - CURL
  - ZIP
- [ ] **MySQL 8.0+** or **MariaDB 10.6+**
- [ ] **Composer 2.x**
- [ ] **Node.js 18+** and **NPM**
- [ ] **Redis** (optional but recommended for caching/queues)
- [ ] **Supervisor** (for queue workers)
- [ ] **Git**

### Domain & Hosting
- [ ] Domain registered and DNS configured
- [ ] SSL certificate obtained (Let's Encrypt recommended)
- [ ] Server/VPS with minimum:
  - 2GB RAM (4GB+ recommended)
  - 2 CPU cores
  - 20GB SSD storage (50GB+ recommended)
  - Ubuntu 22.04 LTS or similar

---

## Security Hardening

### 1. Remove Demo Users

**CRITICAL:** Demo users have known passwords and MUST be removed/disabled in production.

```bash
cd /path/to/skulsoft

# Option 1: Disable all demo users
php artisan tinker
>>> \App\Models\User::where('email', 'like', '%@demo.com%')->update(['status' => 'deactivated']);
>>> exit

# Option 2: Delete all demo users (recommended)
php artisan tinker
>>> \App\Models\User::where('email', 'like', '%@demo.com%')->delete();
>>> exit
```

### 2. Remove ValidateRole Bypass

The demo user bypass in `app/Actions/Auth/ValidateRole.php` MUST be removed:

```php
// REMOVE THIS ENTIRE BLOCK:
// Bypass role validation for demo users
if (Str::endsWith($user->email, '@demo.com')) {
    return $next($user);
}
```

### 3. Change Default Passwords

```bash
# Change admin password
php artisan tinker
>>> $admin = \App\Models\User::where('email', 'admin@skulsoft.com')->first();
>>> $admin->password = bcrypt('YOUR-SECURE-PASSWORD-HERE');
>>> $admin->save();
>>> exit
```

### 4. Secure Environment File

```bash
# Set proper permissions
chmod 600 .env
chown www-data:www-data .env  # or your web server user

# Never commit .env to git
echo ".env" >> .gitignore
```

### 5. Directory Permissions

```bash
# Set proper ownership
chown -R www-data:www-data storage bootstrap/cache

# Set proper permissions
chmod -R 775 storage bootstrap/cache
chmod -R 755 public
```

### 6. Disable Debug Mode

```env
APP_DEBUG=false
APP_ENV=production
```

### 7. Generate New APP_KEY

```bash
php artisan key:generate
```

---

## Environment Configuration

### Production `.env` File

Create/update `.env` with production settings:

```env
# ============================================
# APPLICATION SETTINGS
# ============================================
APP_NAME="SkulSoft"
APP_ENV=production
APP_KEY=base64:YOUR-GENERATED-KEY-HERE
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_MODE=live

# ============================================
# DATABASE CONFIGURATION
# ============================================
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=skulsoft_production
DB_USERNAME=skulsoft_user
DB_PASSWORD=STRONG-DATABASE-PASSWORD-HERE

# ============================================
# CACHE & SESSION
# ============================================
CACHE_DRIVER=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# ============================================
# QUEUE CONFIGURATION
# ============================================
QUEUE_CONNECTION=redis

# ============================================
# REDIS CONFIGURATION
# ============================================
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# ============================================
# MAIL CONFIGURATION
# ============================================
MAIL_MAILER=smtp
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=your-email@yourdomain.com
MAIL_PASSWORD=your-mail-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# ============================================
# FILESYSTEM
# ============================================
FILESYSTEM_DISK=local
# For S3 storage:
# AWS_ACCESS_KEY_ID=
# AWS_SECRET_ACCESS_KEY=
# AWS_DEFAULT_REGION=
# AWS_BUCKET=

# ============================================
# SANCTUM CONFIGURATION
# ============================================
SANCTUM_STATEFUL_DOMAINS=yourdomain.com,www.yourdomain.com

# ============================================
# LOG VIEWER (Disable in production or protect)
# ============================================
LOG_VIEWER_ENABLED=true

# ============================================
# BACKUP CONFIGURATION
# ============================================
BACKUP_NOTIFICATION_EMAIL=admin@yourdomain.com

# ============================================
# PUSHER/BROADCASTING (if using)
# ============================================
BROADCAST_DRIVER=log
# PUSHER_APP_ID=
# PUSHER_APP_KEY=
# PUSHER_APP_SECRET=
# PUSHER_APP_CLUSTER=

# ============================================
# PAYMENT GATEWAYS (configure as needed)
# ============================================
STRIPE_KEY=
STRIPE_SECRET=
RAZORPAY_KEY=
RAZORPAY_SECRET=

# ============================================
# TRUSTED PROXIES
# ============================================
TRUSTED_PROXIES=*
```

### Required Changes

1. **APP_KEY:** Generate new key with `php artisan key:generate`
2. **APP_URL:** Your production domain with HTTPS
3. **DB_DATABASE:** Create unique production database
4. **DB_USERNAME/PASSWORD:** Strong credentials (not root)
5. **MAIL_*:** Configure production mail server
6. **CACHE/SESSION_DRIVER:** Use redis for performance
7. **QUEUE_CONNECTION:** Use redis or database

---

## Database Optimization

### 1. Create Production Database

```sql
-- Login to MySQL
mysql -u root -p

-- Create database
CREATE DATABASE skulsoft_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user with limited privileges
CREATE USER 'skulsoft_user'@'localhost' IDENTIFIED BY 'STRONG-PASSWORD-HERE';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, LOCK TABLES ON skulsoft_production.* TO 'skulsoft_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 2. Run Migrations

```bash
# Backup first!
php artisan backup:run

# Run migrations
php artisan migrate --force

# IMPORTANT: Do NOT run demo data seeders in production!
# Only run essential seeders:
php artisan db:seed --class=PermissionSeeder --force
php artisan db:seed --class=RoleSeeder --force
php artisan db:seed --class=AssignPermissionSeeder --force
```

### 3. Database Optimization

```sql
-- Optimize tables (run periodically)
OPTIMIZE TABLE users, teams, contacts, students, employees;

-- Add indexes for frequently queried fields
ALTER TABLE contacts ADD INDEX idx_email (email);
ALTER TABLE contacts ADD INDEX idx_team_id (team_id);
ALTER TABLE students ADD INDEX idx_batch_id (batch_id);
ALTER TABLE students ADD INDEX idx_period_id (period_id);
```

---

## Performance Optimization

### 1. Cache Configuration

```bash
# Cache configuration files
php artisan config:cache

# Cache routes (be careful with closures)
php artisan route:cache

# Cache views
php artisan view:cache

# Cache events
php artisan event:cache

# Cache icons (if using blade-icons)
php artisan icons:cache
```

### 2. Optimize Autoloader

```bash
composer install --optimize-autoloader --no-dev
```

### 3. Enable OPcache

Add to `php.ini`:

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.fast_shutdown=1
```

### 4. Configure Redis

```bash
# Install Redis
sudo apt-get install redis-server

# Configure Redis persistence
sudo nano /etc/redis/redis.conf

# Set maxmemory policy
maxmemory 256mb
maxmemory-policy allkeys-lru

# Restart Redis
sudo systemctl restart redis-server
```

### 5. Asset Optimization

```bash
# Build production assets
npm run production

# Or with Vite
npm run build

# Enable compression in Nginx/Apache
```

---

## File & Storage Setup

### 1. Storage Link

```bash
php artisan storage:link
```

### 2. File Permissions

```bash
# Set proper permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Prevent execution in uploads
sudo chmod -R 755 storage/app/public
```

### 3. S3 Setup (Optional)

If using AWS S3 for storage:

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=skulsoft-production
AWS_URL=https://skulsoft-production.s3.amazonaws.com
```

---

## Queue & Cron Configuration

### 1. Queue Workers with Supervisor

Create supervisor configuration:

```bash
sudo nano /etc/supervisor/conf.d/skulsoft-worker.conf
```

```ini
[program:skulsoft-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/skulsoft/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/skulsoft/storage/logs/worker.log
stopwaitsecs=3600
```

Apply changes:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start skulsoft-worker:*
```

### 2. Laravel Horizon (Alternative)

```bash
# Install Horizon
composer require laravel/horizon

# Publish config
php artisan horizon:install

# Configure supervisor
sudo nano /etc/supervisor/conf.d/horizon.conf
```

```ini
[program:horizon]
process_name=%(program_name)s
command=php /var/www/skulsoft/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/skulsoft/storage/logs/horizon.log
stopwaitsecs=3600
```

### 3. Cron Jobs

```bash
crontab -e
```

Add Laravel scheduler:

```bash
* * * * * cd /var/www/skulsoft && php artisan schedule:run >> /dev/null 2>&1
```

---

## SSL & Domain Setup

### 1. Install Certbot (Let's Encrypt)

```bash
sudo apt-get update
sudo apt-get install certbot python3-certbot-nginx
```

### 2. Obtain SSL Certificate

```bash
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

### 3. Nginx Configuration

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/skulsoft/public;

    index index.php;

    charset utf-8;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # File Upload Size
    client_max_body_size 100M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### 4. Apache Configuration (.htaccess)

Already included in Laravel, but ensure mod_rewrite is enabled:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

---

## Monitoring & Logging

### 1. Log Rotation

```bash
sudo nano /etc/logrotate.d/skulsoft
```

```
/var/www/skulsoft/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
    postrotate
        php /var/www/skulsoft/artisan config:clear > /dev/null 2>&1
    endscript
}
```

### 2. Error Monitoring

Consider integrating:
- **Sentry:** For error tracking
- **New Relic:** For APM
- **Laravel Telescope:** Only in staging (never production)

### 3. Application Monitoring

```bash
# Enable Laravel Horizon dashboard (protect with auth)
# Access at https://yourdomain.com/horizon

# Enable Log Viewer (protect with auth)
# Access at https://yourdomain.com/log-viewer
```

### 4. Server Monitoring

- Set up monitoring tools (Datadog, Prometheus, etc.)
- Monitor disk space, CPU, RAM usage
- Set up alerts for critical issues

---

## Backup Strategy

### 1. Configure Laravel Backup

Already configured in `config/backup.php`. Update notification email:

```env
BACKUP_NOTIFICATION_EMAIL=admin@yourdomain.com
```

### 2. Schedule Daily Backups

Cron already configured. Backups will run daily.

### 3. Test Backup

```bash
php artisan backup:run
```

### 4. List Backups

```bash
php artisan backup:list
```

### 5. Off-site Backup Storage

Configure S3 or similar for backup destination in `config/backup.php`:

```php
'destination' => [
    'disks' => [
        'local',
        's3',  // Add S3 for off-site backup
    ],
],
```

### 6. Database Backup Script

Create manual backup script:

```bash
#!/bin/bash
# backup-database.sh

BACKUP_DIR="/var/backups/skulsoft"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="skulsoft_production"
DB_USER="skulsoft_user"
DB_PASS="your-password"

mkdir -p $BACKUP_DIR

mysqldump -u$DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/skulsoft_$DATE.sql.gz

# Keep only last 30 days
find $BACKUP_DIR -name "skulsoft_*.sql.gz" -mtime +30 -delete

echo "Backup completed: $BACKUP_DIR/skulsoft_$DATE.sql.gz"
```

Make executable and add to cron:

```bash
chmod +x /path/to/backup-database.sh
```

Add to crontab:

```bash
0 2 * * * /path/to/backup-database.sh >> /var/log/skulsoft-backup.log 2>&1
```

---

## Deployment Steps

### 1. Prepare Server

```bash
# Update system
sudo apt-get update && sudo apt-get upgrade -y

# Install required packages
sudo apt-get install -y nginx php8.2-fpm php8.2-cli php8.2-mysql php8.2-xml \
    php8.2-mbstring php8.2-curl php8.2-zip php8.2-gd php8.2-redis \
    mysql-server redis-server git composer supervisor certbot
```

### 2. Clone Repository

```bash
cd /var/www
sudo git clone https://github.com/FrankEWallace/skulsoft.git
cd skulsoft
```

### 3. Install Dependencies

```bash
# PHP dependencies
composer install --optimize-autoloader --no-dev

# Node dependencies and build
npm install
npm run production
```

### 4. Configure Environment

```bash
# Copy and configure .env
cp .env.example .env
nano .env  # Edit with production settings

# Generate app key
php artisan key:generate
```

### 5. Set Permissions

```bash
sudo chown -R www-data:www-data /var/www/skulsoft
sudo chmod -R 775 storage bootstrap/cache
```

### 6. Database Setup

```bash
# Create database (see Database Optimization section)
# Run migrations
php artisan migrate --force

# Run essential seeders only
php artisan db:seed --class=PermissionSeeder --force
php artisan db:seed --class=RoleSeeder --force
php artisan db:seed --class=AssignPermissionSeeder --force
```

### 7. Create Storage Link

```bash
php artisan storage:link
```

### 8. Optimize Application

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 9. Configure Web Server

Set up Nginx/Apache (see SSL & Domain Setup section)

### 10. Start Queue Workers

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start skulsoft-worker:*
```

### 11. Test Application

Visit `https://yourdomain.com` and verify:
- [ ] Home page loads
- [ ] Login works
- [ ] Database connections work
- [ ] File uploads work
- [ ] Email sending works
- [ ] Queues process

---

## Post-Deployment Testing

### 1. Smoke Tests

```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();
>>> exit

# Test cache
php artisan tinker
>>> Cache::put('test', 'value', 60);
>>> Cache::get('test');
>>> exit

# Test queue
php artisan queue:work --once
```

### 2. Security Scan

```bash
# Check for common security issues
composer require enlightn/security-checker --dev
php artisan security:check
```

### 3. Performance Test

- Use tools like Apache Bench or Loader.io
- Test concurrent users
- Monitor server resources

### 4. Functionality Testing

Test all critical features:
- [ ] User registration/login
- [ ] Student management
- [ ] Employee management
- [ ] Fee collection
- [ ] Report generation
- [ ] Excel import/export
- [ ] Email notifications
- [ ] File uploads

---

## Troubleshooting

### 500 Internal Server Error

1. Check Laravel logs: `storage/logs/laravel.log`
2. Check web server logs: `/var/log/nginx/error.log`
3. Verify permissions on `storage` and `bootstrap/cache`
4. Clear all caches: `php artisan optimize:clear`

### Database Connection Issues

1. Verify `.env` database credentials
2. Test connection: `php artisan tinker` then `DB::connection()->getPdo();`
3. Check MySQL user privileges
4. Verify MySQL is running: `sudo systemctl status mysql`

### Queue Not Processing

1. Check supervisor status: `sudo supervisorctl status`
2. Check worker logs: `tail -f storage/logs/worker.log`
3. Restart workers: `sudo supervisorctl restart skulsoft-worker:*`
4. Check Redis: `redis-cli ping`

### Email Not Sending

1. Verify MAIL_* settings in `.env`
2. Test mail config: `php artisan config:testMailConnection`
3. Check mail logs: `storage/logs/laravel.log`
4. Verify SMTP credentials

### Permission Denied Errors

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Cache Issues

```bash
# Clear all caches
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

---

## Maintenance Mode

### Enable Maintenance Mode

```bash
# With custom message
php artisan down --message="Scheduled maintenance in progress" --retry=60

# With secret bypass token
php artisan down --secret="your-secret-token"
# Access with: https://yourdomain.com/your-secret-token
```

### Disable Maintenance Mode

```bash
php artisan up
```

---

## Zero-Downtime Deployment

For production updates with zero downtime:

1. Use deployment tools like Envoyer or Deployer
2. Use blue-green deployment strategy
3. Keep multiple releases and symlink current

Example structure:

```
/var/www/skulsoft/
├── current -> releases/20260228120000
├── releases/
│   ├── 20260228120000/
│   ├── 20260227100000/
│   └── 20260226080000/
└── storage/  # Shared across releases
```

---

## Security Best Practices Checklist

- [ ] Remove all demo users
- [ ] Remove ValidateRole bypass for demo users
- [ ] Change all default passwords
- [ ] APP_DEBUG=false
- [ ] APP_ENV=production
- [ ] Strong database passwords
- [ ] HTTPS enabled
- [ ] Security headers configured
- [ ] File permissions correct (775 for storage, 755 for public)
- [ ] .env file has 600 permissions
- [ ] Firewall configured (UFW or similar)
- [ ] Regular security updates scheduled
- [ ] Database backups automated
- [ ] Error logs monitored
- [ ] Failed login attempts monitored
- [ ] Two-factor authentication enabled (optional)

---

## Performance Checklist

- [ ] OPcache enabled
- [ ] Redis configured for cache/sessions
- [ ] Queue workers running
- [ ] Assets compiled for production
- [ ] Config cached
- [ ] Routes cached
- [ ] Views cached
- [ ] Database indexes optimized
- [ ] CDN configured (optional)
- [ ] Image optimization configured

---

## Compliance & Data Protection

### GDPR Compliance

- Implement data export functionality
- Implement data deletion functionality
- Add privacy policy page
- Add terms of service
- Implement consent management
- Regular data audits

### Data Retention

- Configure automatic data cleanup
- Archive old records
- Implement data purging policies

---

## Support & Documentation

- **Official Documentation:** Located in `/documentation` folder
- **GitHub Repository:** https://github.com/FrankEWallace/skulsoft
- **Support:** Contact FW Technologies

---

## Quick Reference Commands

```bash
# Clear all caches
php artisan optimize:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Queue workers
sudo supervisorctl restart skulsoft-worker:*

# Backup
php artisan backup:run

# Maintenance mode
php artisan down
php artisan up

# Check logs
tail -f storage/logs/laravel.log

# Database backup
mysqldump -u user -p database > backup.sql
```

---

**Production deployment is complete! Monitor your application closely for the first 48 hours.**

For additional support, refer to the comprehensive documentation in the `/documentation` folder.

---

**Document Version:** 1.0  
**Created:** February 28, 2026  
**For:** SkulSoft School Management System  
**By:** FW Technologies
