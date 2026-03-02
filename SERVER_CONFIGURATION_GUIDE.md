# 🖥️ Server Configuration Guide - SkulSoft

**Last Updated:** February 28, 2026  
**Target Environment:** Production Server

---

## Table of Contents

1. [Server Requirements](#server-requirements)
2. [Nginx Configuration](#nginx-configuration)
3. [Apache Configuration](#apache-configuration)
4. [PHP Configuration](#php-configuration)
5. [MySQL Configuration](#mysql-configuration)
6. [Redis Configuration](#redis-configuration)
7. [Supervisor Configuration](#supervisor-configuration)
8. [Cron Jobs](#cron-jobs)
9. [SSL Certificate](#ssl-certificate)
10. [Firewall](#firewall)

---

## Server Requirements

### Minimum Requirements
- **OS:** Ubuntu 20.04 LTS or later (or equivalent)
- **PHP:** 8.2 or higher
- **Database:** MySQL 8.0+ or MariaDB 10.3+
- **Web Server:** Nginx 1.18+ or Apache 2.4+
- **RAM:** 2GB minimum (4GB+ recommended)
- **CPU:** 2 cores minimum
- **Disk:** 20GB minimum SSD

### Required PHP Extensions
```bash
sudo apt-get install -y \
    php8.2-fpm \
    php8.2-cli \
    php8.2-mysql \
    php8.2-redis \
    php8.2-xml \
    php8.2-mbstring \
    php8.2-curl \
    php8.2-zip \
    php8.2-gd \
    php8.2-bcmath \
    php8.2-intl \
    php8.2-imagick
```

---

## Nginx Configuration

### Install Nginx
```bash
sudo apt-get update
sudo apt-get install nginx
```

### Create Site Configuration

File: `/etc/nginx/sites-available/skulsoft.conf`

```nginx
# SkulSoft School Management System - Nginx Configuration
server {
    listen 80;
    listen [::]:80;
    server_name yourschool.com www.yourschool.com;
    
    # Redirect all HTTP to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourschool.com www.yourschool.com;
    
    root /var/www/skulsoft/public;
    index index.php index.html;
    
    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/yourschool.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourschool.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers 'ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384';
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    
    # Security Headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    
    # Logging
    access_log /var/log/nginx/skulsoft_access.log;
    error_log /var/log/nginx/skulsoft_error.log;
    
    # Client body size (for file uploads)
    client_max_body_size 100M;
    
    # Hide Nginx version
    server_tokens off;
    
    # Charset
    charset utf-8;
    
    # Main location
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        
        # Increase timeouts for long-running scripts
        fastcgi_read_timeout 300;
        fastcgi_send_timeout 300;
    }
    
    # Block access to sensitive files
    location ~ /\.(?!well-known).* {
        deny all;
    }
    
    location ~ /\.env {
        deny all;
    }
    
    location ~ /\.git {
        deny all;
    }
    
    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Deny access to specific files
    location ~ /\.(htaccess|htpasswd) {
        deny all;
    }
    
    # Disable logging for favicon and robots
    location = /favicon.ico { 
        access_log off; 
        log_not_found off; 
    }
    
    location = /robots.txt  { 
        access_log off; 
        log_not_found off; 
    }
}
```

### Enable Site
```bash
sudo ln -s /etc/nginx/sites-available/skulsoft.conf /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

---

## Apache Configuration

### Install Apache
```bash
sudo apt-get update
sudo apt-get install apache2
```

### Enable Required Modules
```bash
sudo a2enmod rewrite
sudo a2enmod ssl
sudo a2enmod headers
sudo a2enmod expires
```

### Create Virtual Host

File: `/etc/apache2/sites-available/skulsoft.conf`

```apache
# SkulSoft School Management System - Apache Configuration

# HTTP - Redirect to HTTPS
<VirtualHost *:80>
    ServerName yourschool.com
    ServerAlias www.yourschool.com
    
    Redirect permanent / https://yourschool.com/
</VirtualHost>

# HTTPS
<VirtualHost *:443>
    ServerName yourschool.com
    ServerAlias www.yourschool.com
    
    DocumentRoot /var/www/skulsoft/public
    
    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/yourschool.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/yourschool.com/privkey.pem
    SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1
    SSLCipherSuite ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256
    SSLHonorCipherOrder off
    
    # Security Headers
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    
    # Hide Apache version
    ServerSignature Off
    ServerTokens Prod
    
    # Logging
    ErrorLog ${APACHE_LOG_DIR}/skulsoft_error.log
    CustomLog ${APACHE_LOG_DIR}/skulsoft_access.log combined
    
    # Directory Configuration
    <Directory /var/www/skulsoft/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # Laravel Rewrite Rules
        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteRule ^ index.php [L]
        </IfModule>
    </Directory>
    
    # Block sensitive files
    <FilesMatch "^\.env">
        Require all denied
    </FilesMatch>
    
    <DirectoryMatch "^/.*/\.git/">
        Require all denied
    </DirectoryMatch>
    
    # Upload size limit
    php_value upload_max_filesize 100M
    php_value post_max_size 100M
</VirtualHost>
```

### Enable Site
```bash
sudo a2ensite skulsoft.conf
sudo apache2ctl configtest
sudo systemctl restart apache2
```

---

## PHP Configuration

### PHP-FPM Configuration

File: `/etc/php/8.2/fpm/pool.d/skulsoft.conf`

```ini
[skulsoft]
user = www-data
group = www-data
listen = /var/run/php/php8.2-fpm-skulsoft.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500

; PHP settings
php_admin_value[error_log] = /var/log/php/skulsoft-error.log
php_admin_flag[log_errors] = on
php_admin_value[memory_limit] = 256M
php_value[upload_max_filesize] = 100M
php_value[post_max_size] = 100M
php_value[max_execution_time] = 300
php_value[max_input_time] = 300
```

### PHP.ini Production Settings

File: `/etc/php/8.2/fpm/php.ini`

```ini
; Basic Settings
max_execution_time = 300
max_input_time = 300
memory_limit = 256M

; File Uploads
file_uploads = On
upload_max_filesize = 100M
post_max_size = 100M
max_file_uploads = 20

; Error Handling (PRODUCTION)
display_errors = Off
display_startup_errors = Off
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
log_errors = On
error_log = /var/log/php/php_errors.log

; Security
expose_php = Off
allow_url_fopen = Off
allow_url_include = Off
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source

; Session
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
session.cookie_samesite = Lax

; OPcache (Performance)
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 60
opcache.fast_shutdown = 1
```

### Restart PHP-FPM
```bash
sudo systemctl restart php8.2-fpm
```

---

## MySQL Configuration

### Create Database and User

```sql
-- Connect to MySQL
mysql -u root -p

-- Create database
CREATE DATABASE skulsoft_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user with strong password
CREATE USER 'skulsoft_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';

-- Grant privileges
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, 
      LOCK TABLES, CREATE TEMPORARY TABLES ON skulsoft_production.* 
      TO 'skulsoft_user'@'localhost';

-- Apply changes
FLUSH PRIVILEGES;

-- Exit
EXIT;
```

### MySQL Production Settings

File: `/etc/mysql/mysql.conf.d/skulsoft.cnf`

```ini
[mysqld]
# Performance
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT

# Character Set
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci

# Max Connections
max_connections = 200

# Query Cache (if needed)
query_cache_type = 1
query_cache_size = 32M

# Logging
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow-queries.log
long_query_time = 2
```

### Restart MySQL
```bash
sudo systemctl restart mysql
```

---

## Redis Configuration

### Install Redis
```bash
sudo apt-get install redis-server
```

### Redis Configuration

File: `/etc/redis/redis.conf`

```conf
# Network
bind 127.0.0.1
protected-mode yes
port 6379

# Memory
maxmemory 256mb
maxmemory-policy allkeys-lru

# Persistence
save 900 1
save 300 10
save 60 10000

# Logging
loglevel notice
logfile /var/log/redis/redis-server.log

# Security (optional - uncomment and set password)
# requirepass YOUR_REDIS_PASSWORD

# Performance
tcp-backlog 511
timeout 0
tcp-keepalive 300
```

### Enable and Start Redis
```bash
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

---

## Supervisor Configuration

### Install Supervisor
```bash
sudo apt-get install supervisor
```

### Queue Worker Configuration

File: `/etc/supervisor/conf.d/skulsoft-worker.conf`

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

### Laravel Horizon Configuration (Alternative)

File: `/etc/supervisor/conf.d/skulsoft-horizon.conf`

```ini
[program:skulsoft-horizon]
process_name=%(program_name)s
command=php /var/www/skulsoft/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/skulsoft/storage/logs/horizon.log
stopwaitsecs=3600
```

### Start Supervisor
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start skulsoft-worker:*
```

---

## Cron Jobs

### Laravel Scheduler

Add to crontab:
```bash
sudo crontab -e -u www-data
```

Add this line:
```cron
* * * * * cd /var/www/skulsoft && php artisan schedule:run >> /dev/null 2>&1
```

### Database Backup Cron

```cron
# Daily database backup at 2 AM
0 2 * * * cd /var/www/skulsoft && php artisan backup:run >> /var/log/backup.log 2>&1

# Weekly cleanup of old backups (keep 14 days)
0 3 * * 0 cd /var/www/skulsoft && php artisan backup:clean >> /var/log/backup.log 2>&1
```

---

## SSL Certificate

### Using Let's Encrypt (Free)

```bash
# Install Certbot
sudo apt-get install certbot python3-certbot-nginx

# For Nginx
sudo certbot --nginx -d yourschool.com -d www.yourschool.com

# For Apache
sudo certbot --apache -d yourschool.com -d www.yourschool.com

# Auto-renewal (already configured by certbot)
sudo certbot renew --dry-run
```

### Manual Certificate Installation

If you have a purchased SSL certificate:

1. Upload certificate files to server
2. Update Nginx/Apache configuration with paths
3. Restart web server

---

## Firewall

### UFW (Uncomplicated Firewall)

```bash
# Enable UFW
sudo ufw enable

# Allow SSH (change port if not 22)
sudo ufw allow 22/tcp

# Allow HTTP
sudo ufw allow 80/tcp

# Allow HTTPS
sudo ufw allow 443/tcp

# Check status
sudo ufw status

# Block all other ports by default
sudo ufw default deny incoming
sudo ufw default allow outgoing
```

### Additional Security

```bash
# Install and configure Fail2Ban
sudo apt-get install fail2ban
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

---

## Verification Commands

```bash
# Check PHP version
php -v

# Check PHP modules
php -m

# Check Nginx configuration
sudo nginx -t

# Check Apache configuration
sudo apache2ctl configtest

# Check MySQL connection
mysql -u skulsoft_user -p -e "SELECT 1"

# Check Redis
redis-cli ping

# Check Supervisor
sudo supervisorctl status

# Check cron jobs
sudo crontab -l -u www-data

# Check SSL certificate
sudo certbot certificates

# Check open ports
sudo netstat -tulpn
```

---

## Performance Optimization

### Enable OPcache
Already configured in php.ini above

### Enable Redis for Sessions and Cache
Already configured in `.env.production`

### Enable HTTP/2
Already configured in Nginx/Apache above

### Optimize MySQL
Already configured in MySQL settings above

### CDN (Optional)
Consider using Cloudflare or similar CDN for static assets

---

## Maintenance Commands

```bash
# Restart all services
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
sudo systemctl restart mysql
sudo systemctl restart redis-server
sudo supervisorctl restart skulsoft-worker:*

# View logs
sudo tail -f /var/log/nginx/skulsoft_error.log
sudo tail -f /var/log/php/php_errors.log
sudo tail -f /var/www/skulsoft/storage/logs/laravel.log

# Clear Laravel caches
cd /var/www/skulsoft
php artisan optimize:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

**Last Updated:** February 28, 2026  
**Maintained By:** FW Technologies  
**Support:** [support email]
