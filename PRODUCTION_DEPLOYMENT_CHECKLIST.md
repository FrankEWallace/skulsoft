# 🚀 Production Deployment Checklist

**Project:** SkulSoft School Management System  
**Date:** February 28, 2026  
**Status:** Pre-Production

---

## Pre-Deployment (1 Week Before)

### Code Preparation
- [ ] All features tested in staging environment
- [ ] All bugs fixed and verified
- [ ] Code reviewed and approved
- [ ] Git repository up to date
- [ ] Create deployment branch/tag
- [ ] Run production preparation script: `php prepare_production.php`

### Security Hardening
- [ ] **CRITICAL:** Remove demo user bypass from ValidateRole ✅ DONE
- [ ] Delete all demo users (@demo.com)
- [ ] Change default admin password
- [ ] Review SECURITY_AUDIT_CHECKLIST.md
- [ ] Run security scan: `composer audit`
- [ ] Update all dependencies
- [ ] Remove development packages

### Environment Configuration
- [ ] Copy `.env.production` to `.env`
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Generate new `APP_KEY`
- [ ] Configure production database credentials
- [ ] Configure production mail server
- [ ] Configure Redis (recommended)
- [ ] Set strong database password
- [ ] Configure payment gateway credentials
- [ ] Set file permissions: `chmod 600 .env`

### Database
- [ ] Create production database
- [ ] Create database user with minimal privileges
- [ ] Test database connection
- [ ] Run migrations: `php artisan migrate --force`
- [ ] Run essential seeders only (NO demo seeders):
  ```bash
  php artisan db:seed --class=PermissionSeeder
  php artisan db:seed --class=RoleSeeder
  php artisan db:seed --class=AssignPermissionSeeder
  ```
- [ ] Create first admin user manually
- [ ] Verify database backup system

### Server Setup
- [ ] PHP 8.2+ installed
- [ ] Required PHP extensions enabled
- [ ] Composer installed
- [ ] Node.js and NPM installed
- [ ] Redis installed and configured
- [ ] SSL certificate installed
- [ ] Configure web server (Nginx/Apache)
- [ ] Configure firewall rules
- [ ] Set up cron jobs for Laravel scheduler

---

## Deployment Day

### 1. Backup Current System (if updating)
```bash
# Backup database
php artisan backup:run

# Backup files
tar -czf backup_$(date +%Y%m%d).tar.gz .

# Move backup off-server
```

### 2. Deploy Code
```bash
# Clone repository or pull latest
git clone https://github.com/FrankEWallace/skulsoft.git
cd skulsoft

# Or if updating
git pull origin main

# Install dependencies (production only)
composer install --optimize-autoloader --no-dev
npm install --production
npm run build
```

### 3. Configure Environment
```bash
# Copy and edit environment file
cp .env.production .env
nano .env

# Generate application key
php artisan key:generate

# Set file permissions
chmod 600 .env
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 4. Database Migration
```bash
# Run migrations
php artisan migrate --force

# Seed essential data
php artisan db:seed --class=PermissionSeeder --force
php artisan db:seed --class=RoleSeeder --force
php artisan db:seed --class=AssignPermissionSeeder --force
```

### 5. Optimize Application
```bash
# Clear all caches
php artisan optimize:clear

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Create storage link
php artisan storage:link
```

### 6. Start Services
```bash
# Start queue workers (using supervisor)
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start skulsoft-worker:*

# Start Laravel scheduler (cron)
# Add to crontab:
# * * * * * cd /path/to/skulsoft && php artisan schedule:run >> /dev/null 2>&1

# Restart web server
sudo systemctl restart nginx
# or
sudo systemctl restart apache2
```

### 7. Verify Deployment
- [ ] Access application URL (https://yourschool.com)
- [ ] Test HTTPS (should redirect from HTTP)
- [ ] Test login with admin account
- [ ] Check all main features
- [ ] Test file uploads
- [ ] Test email sending
- [ ] Test payment gateway (test mode first)
- [ ] Check queue processing
- [ ] Verify scheduled tasks
- [ ] Review error logs

---

## Post-Deployment (First 24 Hours)

### Monitoring
- [ ] Monitor error logs: `tail -f storage/logs/laravel.log`
- [ ] Monitor server resources (CPU, RAM, Disk)
- [ ] Monitor database performance
- [ ] Monitor queue processing
- [ ] Check for failed jobs: `php artisan queue:failed`
- [ ] Monitor user activity
- [ ] Check backup completion

### Performance
- [ ] Test page load times
- [ ] Test database query performance
- [ ] Verify caching working correctly
- [ ] Check Redis connection
- [ ] Monitor API response times
- [ ] Test under expected load

### Security
- [ ] Verify SSL certificate working
- [ ] Check security headers
- [ ] Test CSRF protection
- [ ] Verify file upload restrictions
- [ ] Test authentication/authorization
- [ ] Check for exposed sensitive data
- [ ] Verify rate limiting working

### Communication
- [ ] Announce go-live to stakeholders
- [ ] Provide login credentials to administrators
- [ ] Send welcome emails to initial users
- [ ] Update DNS if needed
- [ ] Update any API documentation

---

## Ongoing Maintenance

### Daily
- [ ] Check error logs
- [ ] Monitor system health
- [ ] Verify backups completed
- [ ] Review user activity

### Weekly
- [ ] Review security logs
- [ ] Check failed jobs
- [ ] Monitor disk space
- [ ] Review performance metrics
- [ ] Test backup restoration

### Monthly
- [ ] Update dependencies
- [ ] Review and rotate logs
- [ ] Security audit
- [ ] Performance optimization
- [ ] User feedback review

---

## Rollback Plan

If deployment fails:

### 1. Stop New Deployment
```bash
# Stop queue workers
sudo supervisorctl stop skulsoft-worker:*

# Put application in maintenance mode
php artisan down
```

### 2. Restore Previous Version
```bash
# Restore code
git checkout <previous-tag>
composer install
npm install && npm run build

# Restore database (if needed)
php artisan backup:restore
# or manually restore from backup
```

### 3. Rollback Database (if migrations ran)
```bash
# Rollback migrations
php artisan migrate:rollback
```

### 4. Restart Services
```bash
# Clear caches
php artisan optimize:clear
php artisan config:cache

# Restart workers
sudo supervisorctl start skulsoft-worker:*

# Bring application back up
php artisan up
```

### 5. Verify Rollback
- [ ] Test application functionality
- [ ] Check error logs
- [ ] Verify data integrity
- [ ] Notify stakeholders

---

## Critical Commands Reference

```bash
# Maintenance mode
php artisan down --secret="emergency-access"
php artisan up

# Clear all caches
php artisan optimize:clear

# Cache for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Database
php artisan migrate --force
php artisan migrate:rollback
php artisan migrate:status

# Queue
php artisan queue:work --daemon
php artisan queue:restart
php artisan queue:failed

# Permissions
chmod 600 .env
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Logs
tail -f storage/logs/laravel.log
tail -f /var/log/nginx/error.log
```

---

## Environment-Specific Files

### DO NOT Deploy to Production
- `.env.example`
- `phpunit.xml`
- `prepare_production.php` (optional - for reference)
- `demo_users.sql`
- `*DemoDataSeeder.php` files
- Development packages in `composer.json`

### Must Be Configured
- `.env` (copy from `.env.production`)
- Web server configuration
- SSL certificate
- Cron jobs
- Supervisor configuration
- Firewall rules

---

## Server Requirements

### PHP Extensions
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
- Redis (recommended)

### Server Software
- PHP 8.2+
- MySQL 8.0+ or MariaDB 10.3+
- Nginx or Apache
- Redis 6.0+
- Composer 2.x
- Node.js 18+
- Supervisor (for queue workers)

### Recommended Resources
- RAM: 2GB minimum, 4GB+ recommended
- CPU: 2 cores minimum
- Disk: 20GB minimum (depends on data volume)
- Bandwidth: Based on expected users

---

## Support Contacts

- **Technical Lead:** [Your Name]
- **System Administrator:** [Name]
- **Database Administrator:** [Name]
- **Security Contact:** [Name]
- **Emergency Hotline:** [Number]

---

## Documentation References

- [SECURITY_AUDIT_CHECKLIST.md](SECURITY_AUDIT_CHECKLIST.md)
- [CPANEL_DEPLOYMENT_GUIDE.md](CPANEL_DEPLOYMENT_GUIDE.md)
- [LOGIN_CREDENTIALS.md](LOGIN_CREDENTIALS.md)
- [README.md](README.md)

---

**Last Updated:** February 28, 2026  
**Version:** 1.0  
**Deployment Status:** 🔴 NOT YET DEPLOYED

---

## Sign-Off

Before deploying to production, the following must sign off:

- [ ] **Developer:** _________________ Date: _______
- [ ] **QA Lead:** _________________ Date: _______
- [ ] **Security Officer:** _________________ Date: _______
- [ ] **Project Manager:** _________________ Date: _______
- [ ] **Client/Stakeholder:** _________________ Date: _______

**Deployment approved:** ⬜ YES  ⬜ NO

**Deployment Date/Time:** _________________

**Deployed By:** _________________
