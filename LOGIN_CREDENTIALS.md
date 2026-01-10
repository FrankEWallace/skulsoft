# Authentication & Credentials

- [Default Credentials](#default-credentials)
- [Environment Configuration](#environment-configuration)
- [User Management](#user-management)
- [Artisan Commands](#artisan-commands)
- [Security Best Practices](#security-best-practices)

<a name="default-credentials"></a>
## Default Credentials

The application comes with a pre-configured administrator account. Use these credentials for initial access:

| Field | Value |
|-------|-------|
| **URL** | http://127.0.0.1:8002 |
| **Email** | admin@skulsoft.com |
| **Password** | admin123 |
| **Username** | admin |

> **Warning:** For security reasons, change the default password immediately after your first login.

<a name="environment-configuration"></a>
## Environment Configuration

### Database Configuration

Ensure your `.env` file contains the correct database settings:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=8889
DB_DATABASE=SkulSoft
DB_USERNAME=root
DB_PASSWORD=root
```

### Application Settings

```env
APP_NAME=SkulSoft
APP_ENV=local
APP_KEY=base64:your-app-key-here
APP_DEBUG=true
APP_URL=http://127.0.0.1:8002
```

### Multi-Tenancy Settings

The application uses team-based multi-tenancy with the following defaults:

- **Default Team:** SkulSoft School (ID: 1)
- **Default Period:** 2026-2027 (ID: 1)
- **Guard:** web

<a name="user-management"></a>
## User Management

### Creating New Users

Use Laravel Tinker to create additional users programmatically:

```bash
php artisan tinker
```

```php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@skulsoft.com',
    'username' => 'johndoe',
    'password' => Hash::make('secure-password'),
    'status' => 'activated',
    'email_verified_at' => now(),
    'meta' => [
        'current_team_id' => 1,
        'current_period_id' => 1,
    ],
]);

// Assign admin role with team scope
$user->assignRole('admin');
```

### Resetting Passwords

To reset a user's password via Tinker:

```bash
php artisan tinker
```

```php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

$user = User::where('email', 'admin@skulsoft.com')->first();
$user->password = Hash::make('new-secure-password');
$user->save();
```

### User Status Management

Valid user statuses:
- `activated` - User can login
- `deactivated` - User is blocked from login
- `pending` - User awaiting activation

```php
// Activate a user
$user = User::find(1);
$user->status = 'activated';
$user->save();

// Deactivate a user
$user->status = 'deactivated';
$user->save();
```

<a name="artisan-commands"></a>
## Artisan Commands

### Running the Development Server

```bash
php artisan serve
```

By default, the server will attempt to start on port 8000. If occupied, it will try 8001, then 8002.

You can specify a port manually:

```bash
php artisan serve --port=8080
```

### Database Operations

```bash
# Run migrations
php artisan migrate

# Run migrations with force (production)
php artisan migrate --force

# Rollback last migration
php artisan migrate:rollback

# Fresh migration with seed
php artisan migrate:fresh --seed
```

### Seeding Data

```bash
# Run all seeders
php artisan db:seed

# Run specific seeder
php artisan db:seed --class=PermissionSeeder
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=AssignPermissionSeeder
```

### Cache Management

```bash
# Clear all caches
php artisan optimize:clear

# Clear specific caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Cache configuration for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Reset permission cache (Spatie)
php artisan permission:cache-reset
```

### Queue Management

```bash
# Run queue worker
php artisan queue:work

# Run queue with specific connection
php artisan queue:work database

# Process only one job
php artisan queue:work --once
```

<a name="security-best-practices"></a>
## Security Best Practices

### Password Requirements

Implement strong password policies:
- Minimum 8 characters
- Include uppercase and lowercase letters
- Include at least one number
- Include at least one special character

### Role-Based Access Control (RBAC)

The application uses [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission) for roles and permissions:

```php
// Check if user has permission
if ($user->can('manage-students')) {
    // User has permission
}

// Check if user has role
if ($user->hasRole('admin')) {
    // User is admin
}

// Assign permission to role
$role = Role::findByName('admin');
$role->givePermissionTo('manage-fees');

// Sync all permissions for a role
$permissions = Permission::all();
$role->syncPermissions($permissions);
```

### Team-Based Isolation

All users must be associated with a team:

```php
// Set user's current team
$user->meta = [
    'current_team_id' => 1,
    'current_period_id' => 1,
];
$user->save();

// Query data for specific team
$students = Student::where('team_id', auth()->user()->meta['current_team_id'])->get();
```

### Email Verification

Ensure users verify their email addresses:

```php
// Mark email as verified
$user->email_verified_at = now();
$user->save();

// Check if email is verified
if ($user->hasVerifiedEmail()) {
    // Email is verified
}
```

## Troubleshooting

### Cannot Login

If you're experiencing login issues:

1. **Verify user status:**
   ```bash
   php artisan tinker
   ```
   ```php
   $user = User::where('email', 'admin@skulsoft.com')->first();
   echo "Status: {$user->status}\n";
   echo "Verified: {$user->email_verified_at}\n";
   ```

2. **Check role assignment:**
   ```php
   $user = User::find(1);
   echo "Roles: " . $user->getRoleNames() . "\n";
   echo "Permissions: " . $user->getAllPermissions()->pluck('name') . "\n";
   ```

3. **Verify team association:**
   ```php
   $user = User::find(1);
   print_r($user->meta);
   ```

4. **Clear all caches:**
   ```bash
   php artisan optimize:clear
   php artisan permission:cache-reset
   ```

### Server Not Starting

If `php artisan serve` fails:

1. Check if port is in use:
   ```bash
   lsof -i :8000
   ```

2. Use a different port:
   ```bash
   php artisan serve --port=8080
   ```

3. Check Laravel logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

## Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Spatie Permission Package](https://spatie.be/docs/laravel-permission)
- [Laravel Tinker Documentation](https://laravel.com/docs/artisan#tinker)
- [FW Technologies Support](https://fwtechnologies.com)

---

**Laravel Version:** 11.x  
**Last Updated:** 11 January 2026  
**Maintained By:** FW Technologies
