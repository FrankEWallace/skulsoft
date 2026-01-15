<?php return array (
  8 => 'concurrency',
  'activitylog' => 
  array (
    'enabled' => true,
    'delete_records_older_than_days' => 15,
    'default_log_name' => 'default',
    'default_auth_driver' => NULL,
    'subject_returns_soft_deleted_models' => false,
    'activity_model' => 'Spatie\\Activitylog\\Models\\Activity',
    'table_name' => 'activity_log',
    'database_connection' => NULL,
  ),
  'app' => 
  array (
    'name' => 'SkulSoft',
    'env' => 'local',
    'debug' => true,
    'url' => 'http://127.0.0.1:8002',
    'frontend_url' => 'http://localhost:3000',
    'asset_url' => NULL,
    'timezone' => 'UTC',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'faker_locale' => 'en_US',
    'cipher' => 'AES-256-CBC',
    'key' => 'base64:vtHVIxNZEwTuCq1Xoc/dAi5SNDrNxK2CIdkQS2/IQIE=',
    'previous_keys' => 
    array (
    ),
    'maintenance' => 
    array (
      'driver' => 'file',
      'store' => 'database',
    ),
    'providers' => 
    array (
      0 => 'Illuminate\\Auth\\AuthServiceProvider',
      1 => 'Illuminate\\Broadcasting\\BroadcastServiceProvider',
      2 => 'Illuminate\\Bus\\BusServiceProvider',
      3 => 'Illuminate\\Cache\\CacheServiceProvider',
      4 => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
      5 => 'Illuminate\\Cookie\\CookieServiceProvider',
      6 => 'Illuminate\\Database\\DatabaseServiceProvider',
      7 => 'Illuminate\\Encryption\\EncryptionServiceProvider',
      8 => 'Illuminate\\Filesystem\\FilesystemServiceProvider',
      9 => 'Illuminate\\Foundation\\Providers\\FoundationServiceProvider',
      10 => 'Illuminate\\Hashing\\HashServiceProvider',
      11 => 'Illuminate\\Mail\\MailServiceProvider',
      12 => 'Illuminate\\Notifications\\NotificationServiceProvider',
      13 => 'Illuminate\\Pagination\\PaginationServiceProvider',
      14 => 'Illuminate\\Pipeline\\PipelineServiceProvider',
      15 => 'Illuminate\\Queue\\QueueServiceProvider',
      16 => 'Illuminate\\Redis\\RedisServiceProvider',
      17 => 'Illuminate\\Auth\\Passwords\\PasswordResetServiceProvider',
      18 => 'Illuminate\\Session\\SessionServiceProvider',
      19 => 'Illuminate\\Translation\\TranslationServiceProvider',
      20 => 'Illuminate\\Validation\\ValidationServiceProvider',
      21 => 'Illuminate\\View\\ViewServiceProvider',
      22 => 'App\\Providers\\InitServiceProvider',
      23 => 'App\\Providers\\AppServiceProvider',
      24 => 'App\\Providers\\AuthServiceProvider',
      25 => 'App\\Providers\\BroadcastServiceProvider',
      26 => 'App\\Providers\\EventServiceProvider',
      27 => 'App\\Providers\\HorizonServiceProvider',
      28 => 'App\\Providers\\RouteServiceProvider',
      29 => 'App\\Providers\\FolioServiceProvider',
    ),
    'aliases' => 
    array (
      'App' => 'Illuminate\\Support\\Facades\\App',
      'Arr' => 'Illuminate\\Support\\Arr',
      'Artisan' => 'Illuminate\\Support\\Facades\\Artisan',
      'Auth' => 'Illuminate\\Support\\Facades\\Auth',
      'Blade' => 'Illuminate\\Support\\Facades\\Blade',
      'Broadcast' => 'Illuminate\\Support\\Facades\\Broadcast',
      'Bus' => 'Illuminate\\Support\\Facades\\Bus',
      'Cache' => 'Illuminate\\Support\\Facades\\Cache',
      'Concurrency' => 'Illuminate\\Support\\Facades\\Concurrency',
      'Config' => 'Illuminate\\Support\\Facades\\Config',
      'Context' => 'Illuminate\\Support\\Facades\\Context',
      'Cookie' => 'Illuminate\\Support\\Facades\\Cookie',
      'Crypt' => 'Illuminate\\Support\\Facades\\Crypt',
      'Date' => 'Illuminate\\Support\\Facades\\Date',
      'DB' => 'Illuminate\\Support\\Facades\\DB',
      'Eloquent' => 'Illuminate\\Database\\Eloquent\\Model',
      'Event' => 'Illuminate\\Support\\Facades\\Event',
      'File' => 'Illuminate\\Support\\Facades\\File',
      'Gate' => 'Illuminate\\Support\\Facades\\Gate',
      'Hash' => 'Illuminate\\Support\\Facades\\Hash',
      'Http' => 'Illuminate\\Support\\Facades\\Http',
      'Js' => 'Illuminate\\Support\\Js',
      'Lang' => 'Illuminate\\Support\\Facades\\Lang',
      'Log' => 'Illuminate\\Support\\Facades\\Log',
      'Mail' => 'Illuminate\\Support\\Facades\\Mail',
      'Notification' => 'Illuminate\\Support\\Facades\\Notification',
      'Number' => 'Illuminate\\Support\\Number',
      'Password' => 'Illuminate\\Support\\Facades\\Password',
      'Process' => 'Illuminate\\Support\\Facades\\Process',
      'Queue' => 'Illuminate\\Support\\Facades\\Queue',
      'RateLimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
      'Redirect' => 'Illuminate\\Support\\Facades\\Redirect',
      'Request' => 'Illuminate\\Support\\Facades\\Request',
      'Response' => 'Illuminate\\Support\\Facades\\Response',
      'Route' => 'Illuminate\\Support\\Facades\\Route',
      'Schedule' => 'Illuminate\\Support\\Facades\\Schedule',
      'Schema' => 'Illuminate\\Support\\Facades\\Schema',
      'Session' => 'Illuminate\\Support\\Facades\\Session',
      'Storage' => 'Illuminate\\Support\\Facades\\Storage',
      'Str' => 'Illuminate\\Support\\Str',
      'URL' => 'Illuminate\\Support\\Facades\\URL',
      'Uri' => 'Illuminate\\Support\\Uri',
      'Validator' => 'Illuminate\\Support\\Facades\\Validator',
      'View' => 'Illuminate\\Support\\Facades\\View',
      'Vite' => 'Illuminate\\Support\\Facades\\Vite',
      'Site' => 'App\\Facades\\SiteFacade',
      'Cal' => 'App\\Facades\\CalFacade',
      'Price' => 'App\\Facades\\PriceFacade',
      'Percent' => 'App\\Facades\\PercentFacade',
      'Currency' => 'App\\Facades\\CurrencyFacade',
      'Country' => 'App\\Facades\\CountryFacade',
    ),
    'mode' => 'local',
    'mask' => 'xxxxxxxxxx',
    'mobile_compatibility' => '4.7.0',
    'item' => '858790',
    'verifier' => 'https://auth.scriptmint.com',
  ),
  'auth' => 
  array (
    'defaults' => 
    array (
      'guard' => 'web',
      'passwords' => 'users',
    ),
    'guards' => 
    array (
      'web' => 
      array (
        'driver' => 'session',
        'provider' => 'users',
      ),
      'sanctum' => 
      array (
        'driver' => 'sanctum',
        'provider' => NULL,
      ),
    ),
    'providers' => 
    array (
      'users' => 
      array (
        'driver' => 'eloquent',
        'model' => 'App\\Models\\User',
      ),
    ),
    'passwords' => 
    array (
      'users' => 
      array (
        'provider' => 'users',
        'table' => 'password_resets',
        'expire' => 60,
        'throttle' => 60,
      ),
    ),
    'password_timeout' => 10800,
  ),
  'backup' => 
  array (
    'backup' => 
    array (
      'name' => 'backup',
      'source' => 
      array (
        'files' => 
        array (
          'include' => 
          array (
            0 => '/Applications/MAMP/htdocs/shulesoft/school-ms',
          ),
          'exclude' => 
          array (
            0 => '/Applications/MAMP/htdocs/shulesoft/school-ms/.git',
            1 => '/Applications/MAMP/htdocs/shulesoft/school-ms/vendor',
            2 => '/Applications/MAMP/htdocs/shulesoft/school-ms/node_modules',
            3 => '/Applications/MAMP/htdocs/shulesoft/school-ms/public/storage',
            4 => '/Applications/MAMP/htdocs/shulesoft/school-ms/storage/debugbar',
            5 => '/Applications/MAMP/htdocs/shulesoft/school-ms/storage/framework',
            6 => '/Applications/MAMP/htdocs/shulesoft/school-ms/storage/logs',
            7 => '/Applications/MAMP/htdocs/shulesoft/school-ms/storage/app/backup-temp',
          ),
          'follow_links' => false,
          'ignore_unreadable_directories' => false,
          'relative_path' => '/Applications/MAMP/htdocs/shulesoft/school-ms',
        ),
        'databases' => 
        array (
          0 => 'mysql',
        ),
      ),
      'database_dump_compressor' => NULL,
      'database_dump_file_extension' => '',
      'destination' => 
      array (
        'filename_prefix' => '',
        'disks' => 
        array (
          0 => 'local',
        ),
      ),
      'temporary_directory' => '/Applications/MAMP/htdocs/shulesoft/school-ms/storage/app/backup-temp',
      'password' => NULL,
      'encryption' => 'default',
    ),
    'notifications' => 
    array (
      'notifications' => 
      array (
        'Spatie\\Backup\\Notifications\\Notifications\\BackupHasFailedNotification' => 
        array (
        ),
        'Spatie\\Backup\\Notifications\\Notifications\\UnhealthyBackupWasFoundNotification' => 
        array (
        ),
        'Spatie\\Backup\\Notifications\\Notifications\\CleanupHasFailedNotification' => 
        array (
        ),
        'Spatie\\Backup\\Notifications\\Notifications\\BackupWasSuccessfulNotification' => 
        array (
        ),
        'Spatie\\Backup\\Notifications\\Notifications\\HealthyBackupWasFoundNotification' => 
        array (
        ),
        'Spatie\\Backup\\Notifications\\Notifications\\CleanupWasSuccessfulNotification' => 
        array (
        ),
      ),
      'notifiable' => 'Spatie\\Backup\\Notifications\\Notifiable',
      'mail' => 
      array (
        'to' => 'your@example.com',
        'from' => 
        array (
          'address' => 'hello@example.com',
          'name' => 'SkulSoft',
        ),
      ),
      'slack' => 
      array (
        'webhook_url' => '',
        'channel' => NULL,
        'username' => NULL,
        'icon' => NULL,
      ),
    ),
    'monitor_backups' => 
    array (
      0 => 
      array (
        'name' => 'SkulSoft',
        'disks' => 
        array (
          0 => 'local',
        ),
        'health_checks' => 
        array (
          'Spatie\\Backup\\Tasks\\Monitor\\HealthChecks\\MaximumAgeInDays' => 1,
          'Spatie\\Backup\\Tasks\\Monitor\\HealthChecks\\MaximumStorageInMegabytes' => 5000,
        ),
      ),
    ),
    'cleanup' => 
    array (
      'strategy' => 'Spatie\\Backup\\Tasks\\Cleanup\\Strategies\\DefaultStrategy',
      'default_strategy' => 
      array (
        'keep_all_backups_for_days' => 7,
        'keep_daily_backups_for_days' => 16,
        'keep_weekly_backups_for_weeks' => 8,
        'keep_monthly_backups_for_months' => 4,
        'keep_yearly_backups_for_years' => 2,
        'delete_oldest_backups_when_using_more_megabytes_than' => 5000,
      ),
    ),
  ),
  'broadcasting' => 
  array (
    'default' => 'log',
    'connections' => 
    array (
      'reverb' => 
      array (
        'driver' => 'reverb',
        'key' => NULL,
        'secret' => NULL,
        'app_id' => NULL,
        'options' => 
        array (
          'host' => NULL,
          'port' => 443,
          'scheme' => 'https',
          'useTLS' => true,
        ),
        'client_options' => 
        array (
        ),
      ),
      'pusher' => 
      array (
        'driver' => 'pusher',
        'key' => '',
        'secret' => '',
        'app_id' => '',
        'options' => 
        array (
          'cluster' => 'mt1',
          'useTLS' => true,
        ),
        'client_options' => 
        array (
        ),
      ),
      'ably' => 
      array (
        'driver' => 'ably',
        'key' => NULL,
      ),
      'log' => 
      array (
        'driver' => 'log',
      ),
      'null' => 
      array (
        'driver' => 'null',
      ),
      'redis' => 
      array (
        'driver' => 'redis',
        'connection' => 'default',
      ),
    ),
  ),
  'cache' => 
  array (
    'default' => 'file',
    'stores' => 
    array (
      'array' => 
      array (
        'driver' => 'array',
        'serialize' => false,
      ),
      'database' => 
      array (
        'driver' => 'database',
        'table' => 'cache',
        'connection' => NULL,
        'lock_connection' => NULL,
      ),
      'file' => 
      array (
        'driver' => 'file',
        'path' => '/Applications/MAMP/htdocs/shulesoft/school-ms/storage/framework/cache/data',
      ),
      'memcached' => 
      array (
        'driver' => 'memcached',
        'persistent_id' => NULL,
        'sasl' => 
        array (
          0 => NULL,
          1 => NULL,
        ),
        'options' => 
        array (
        ),
        'servers' => 
        array (
          0 => 
          array (
            'host' => '127.0.0.1',
            'port' => 11211,
            'weight' => 100,
          ),
        ),
      ),
      'redis' => 
      array (
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
      ),
      'dynamodb' => 
      array (
        'driver' => 'dynamodb',
        'key' => '',
        'secret' => '',
        'region' => 'us-east-1',
        'table' => 'cache',
        'endpoint' => NULL,
      ),
      'octane' => 
      array (
        'driver' => 'octane',
      ),
      'apc' => 
      array (
        'driver' => 'apc',
      ),
    ),
    'prefix' => 'skulsoft_cache',
  ),
  'config' => 
  array (
    'system' => 
    array (
      'show_setup_wizard' => false,
      'direction' => 'ltr',
      'locale' => 'en',
      'timezone' => 'Asia/Kolkata',
      'color_scheme' => 'default',
      'date_format' => 'MMMM D, YYYY',
      'time_format' => 'h:mm A',
      'per_page' => 10,
      'currency' => 'USD',
      'currencies' => 'USD',
      'footer_credit' => 'Developed by FW Technologies',
      'show_version_number' => true,
      'enable_dark_theme' => false,
      'enable_mini_sidebar' => true,
      'enable_maintenance_mode' => false,
      'enable_setup_wizard' => false,
      'enable_print_preview' => true,
      'enable_strong_password' => true,
      'whitelist_ips' => '',
      'blacklist_ips' => '',
      'default_guest_page_layout' => 'dotted_gradient_background',
      'currency_detail' => 
      array (
        'description' => 'United States Dollar',
        'name' => 'USD',
        'symbol' => '$',
        'icon' => 'dollar-sign',
        'position' => 'prefix',
        'decimal' => 2,
        'thousand' => 3,
        'unit_name' => 'Dollar',
        'sub_unit_name' => 'Cent',
        'decimal_delimeter' => '.',
        'thousand_delimeter' => ',',
      ),
      'upload_prefix' => '',
    ),
    'feature' => 
    array (
      'enable_todo' => true,
      'enable_backup' => true,
      'enable_activity_log' => true,
      'enable_online_registration' => true,
      'online_registration_instruction' => '',
      'enable_post' => true,
      'online_registration_mandatory_upload_field' => 'id_proof,address_proof,signature,marksheet,transfer_certificate',
      'online_registration_version' => 'default',
      'enable_guest_payment' => true,
      'guest_payment_instruction' => '',
      'enable_job_application' => true,
      'job_application_instruction' => '',
      'enable_transfer_certificate_verification' => true,
      'transfer_certificate_verification_instruction' => '',
    ),
    'notification' => 
    array (
      'enable_mail_notification' => true,
      'enable_mobile_push_notification' => false,
      'enable_guest_notification_bar' => false,
      'enable_app_notification_bar' => false,
      'guest_notification_message' => '',
      'app_notification_message' => '',
      'enable_pusher_notification' => false,
      'pusher_app_id' => '',
      'pusher_app_key' => '',
      'pusher_app_secret' => '',
      'pusher_app_cluster' => '',
    ),
    'general' => 
    array (
      'app_name' => 'SkulSoft',
      'meta_author' => 'FW Technologies',
      'meta_description' => 'School Management System by FW Technologies',
      'meta_keywords' => 'scriptmint',
    ),
    'auth' => 
    array (
      'session_lifetime' => 1440,
      'login_throttle_max_attempts' => 5,
      'login_throttle_lock_timeout' => 2,
      'enable_reset_password' => true,
      'reset_password_token_lifetime' => 30,
      'enable_registration' => true,
      'enable_registration_terms' => false,
      'enable_email_verification' => true,
      'enable_account_approval' => true,
      'enable_two_factor_security' => false,
      'enable_oauth_login' => false,
      'google_client_id' => NULL,
      'google_client_secret' => NULL,
      'facebook_client_id' => NULL,
      'facebook_client_secret' => NULL,
      'twitter_client_id' => NULL,
      'twitter_client_secret' => NULL,
      'github_client_id' => NULL,
      'github_client_secret' => NULL,
      'microsoft_client_id' => NULL,
      'microsoft_client_secret' => NULL,
      'enable_otp_login' => false,
      'otp_login_lifetime' => 10,
      'enable_email_otp_login' => false,
      'enable_sms_otp_login' => false,
      'enable_screen_lock' => false,
      'screen_lock_timeout' => 10,
    ),
    'assets' => 
    array (
      'favicon' => 'http://127.0.0.1:8002/images/favicon.png',
      'logo' => 'http://127.0.0.1:8002/images/logo.png',
      'logo_light' => 'http://127.0.0.1:8002/images/logo-light.png',
      'icon' => 'http://127.0.0.1:8002/images/icon.png',
      'icon_light' => 'http://127.0.0.1:8002/images/icon-light.png',
      'guest_background' => 'http://127.0.0.1:8002/images/guest-background.webp',
      'guest_full_page_background' => 'http://127.0.0.1:8002/images/guest-background.webp',
    ),
    'mail' => 
    array (
      'driver' => 'log',
      'from_name' => 'ScriptMint',
      'from_address' => 'hello@scriptmint.com',
      'smtp_username' => '',
      'smtp_password' => '',
      'mailgun_domain' => '',
      'mailgun_secret' => '',
      'mailgun_endpoint' => '',
    ),
    'sms' => 
    array (
      'driver' => NULL,
      'max_sms_per_queue' => 100,
      'sender_id' => NULL,
      'test_number' => NULL,
      'test_template_id' => NULL,
      'api_key' => NULL,
      'api_secret' => NULL,
      'api_url' => NULL,
      'api_method' => 'GET',
      'number_prefix' => NULL,
      'sender_id_param' => NULL,
      'receiver_param' => NULL,
      'message_param' => NULL,
      'template_id_param' => NULL,
      'api_headers' => NULL,
      'additional_params' => NULL,
    ),
    'whatsapp' => 
    array (
      'provider' => NULL,
      'max_whatsapp_per_queue' => 100,
      'sender_id' => NULL,
      'test_number' => NULL,
      'test_template_id' => NULL,
      'account_id' => NULL,
      'api_key' => NULL,
      'api_id' => NULL,
      'api_secret' => NULL,
      'username' => NULL,
      'password' => NULL,
      'api_url' => NULL,
      'api_method' => 'GET',
      'number_prefix' => NULL,
      'sender_id_param' => NULL,
      'receiver_param' => NULL,
      'message_param' => NULL,
      'template_id_param' => NULL,
      'api_headers' => NULL,
      'additional_params' => NULL,
    ),
    'social_network' => 
    array (
    ),
    'utility' => 
    array (
      'todo_view' => 'board',
    ),
    'chat' => 
    array (
      'enable_chat' => true,
    ),
    'site' => 
    array (
      'enable_site' => true,
      'show_public_view' => true,
      'theme' => 'default',
      'color_scheme' => 'default',
    ),
    'blog' => 
    array (
      'enable_blog' => true,
    ),
    'contact' => 
    array (
      'enable_middle_name_field' => false,
      'enable_third_name_field' => false,
      'is_unique_id_number1_enabled' => false,
      'is_unique_id_number2_enabled' => false,
      'is_unique_id_number3_enabled' => false,
      'is_unique_id_number4_enabled' => false,
      'is_unique_id_number5_enabled' => false,
      'unique_id_number1_label' => 'Unique ID Number 1',
      'unique_id_number2_label' => 'Unique ID Number 2',
      'unique_id_number3_label' => 'Unique ID Number 3',
      'unique_id_number4_label' => 'Unique ID Number 4',
      'unique_id_number5_label' => 'Unique ID Number 5',
      'is_unique_id_number1_required' => false,
      'is_unique_id_number2_required' => false,
      'is_unique_id_number3_required' => false,
      'is_unique_id_number4_required' => false,
      'is_unique_id_number5_required' => false,
    ),
    'academic' => 
    array (
      'period_selection' => 'period_wise',
      'allow_listing_subject_wise_student' => true,
    ),
    'student' => 
    array (
      'registration_number_prefix' => 'SM',
      'registration_number_digit' => 3,
      'registration_number_suffix' => '',
      'admission_number_prefix' => 'SM',
      'admission_number_digit' => 3,
      'admission_number_suffix' => '',
      'transfer_request_number_prefix' => 'TR',
      'transfer_request_number_digit' => 3,
      'transfer_request_number_suffix' => '',
      'transfer_number_prefix' => 'T',
      'transfer_number_digit' => 3,
      'transfer_number_suffix' => '',
      'enable_provisional_admission' => false,
      'provisional_admission_number_prefix' => 'PSM',
      'provisional_admission_number_digit' => 3,
      'provisional_admission_number_suffix' => '',
      'attendance_past_day_limit' => 7,
      'is_unique_id_number1_enabled' => true,
      'is_unique_id_number2_enabled' => true,
      'is_unique_id_number3_enabled' => true,
      'is_unique_id_number4_enabled' => true,
      'is_unique_id_number5_enabled' => true,
      'unique_id_number1_label' => 'Unique ID Number 1',
      'unique_id_number2_label' => 'Unique ID Number 2',
      'unique_id_number3_label' => 'Unique ID Number 3',
      'unique_id_number4_label' => 'Unique ID Number 4',
      'unique_id_number5_label' => 'Unique ID Number 5',
      'is_unique_id_number1_required' => false,
      'is_unique_id_number2_required' => false,
      'is_unique_id_number3_required' => false,
      'is_unique_id_number4_required' => false,
      'is_unique_id_number5_required' => false,
      'enable_marital_status' => false,
      'enable_anniversary_date' => false,
      'allow_student_to_submit_contact_edit_request' => true,
      'late_fee_waiver_till_date' => '',
      'allow_flexible_installment_payment' => false,
      'enable_timesheet' => false,
      'allow_student_clock_in_out' => false,
      'enable_qr_code_attendance' => true,
      'has_dynamic_qr_code' => true,
      'qr_code_expiry_duration' => 30,
      'service_request_number_prefix' => 'SR',
      'service_request_number_digit' => 3,
      'service_request_number_suffix' => '',
      'services' => 'mess,transport,hostel',
    ),
    'employee' => 
    array (
      'code_number_prefix' => 'ESM',
      'code_number_digit' => 3,
      'code_number_suffix' => '',
      'default_employee_types' => 'administrative,teaching,support',
      'is_unique_id_number1_enabled' => true,
      'is_unique_id_number2_enabled' => true,
      'is_unique_id_number3_enabled' => true,
      'is_unique_id_number4_enabled' => true,
      'is_unique_id_number5_enabled' => true,
      'unique_id_number1_label' => 'Unique ID Number 1',
      'unique_id_number2_label' => 'Unique ID Number 2',
      'unique_id_number3_label' => 'Unique ID Number 3',
      'unique_id_number4_label' => 'Unique ID Number 4',
      'unique_id_number5_label' => 'Unique ID Number 5',
      'is_unique_id_number1_required' => false,
      'is_unique_id_number2_required' => false,
      'is_unique_id_number3_required' => false,
      'is_unique_id_number4_required' => false,
      'is_unique_id_number5_required' => false,
      'allow_employee_to_submit_contact_edit_request' => true,
      'allow_employee_request_leave_with_exhausted_credit' => false,
      'allow_employee_half_day_leave' => false,
      'attendance_past_day_limit' => 0,
      'allow_employee_clock_in_out' => true,
      'enable_qr_code_attendance' => true,
      'has_dynamic_qr_code' => true,
      'qr_code_expiry_duration' => 30,
      'duration_between_clock_request' => 5,
      'allow_employee_clock_in_out_via_device' => true,
      'late_grace_period' => 15,
      'early_leaving_grace_period' => 15,
      'present_grace_period' => 30,
      'enable_geolocation_timesheet' => false,
      'geolocation_latitude' => '',
      'geolocation_longitude' => '',
      'cache_geolocation_data' => 10000,
      'geolocation_radius' => 100,
      'payroll_number_prefix' => 'PSM',
      'payroll_number_digit' => 3,
      'payroll_number_suffix' => '',
      'enable_payhead_round_off' => false,
      'show_payroll_as_total_component' => false,
    ),
    'guardian' => 
    array (
      'is_unique_id_number1_enabled' => true,
      'is_unique_id_number2_enabled' => true,
      'is_unique_id_number3_enabled' => true,
      'is_unique_id_number4_enabled' => true,
      'is_unique_id_number5_enabled' => true,
      'unique_id_number1_label' => 'Unique ID Number 1',
      'unique_id_number2_label' => 'Unique ID Number 2',
      'unique_id_number3_label' => 'Unique ID Number 3',
      'unique_id_number4_label' => 'Unique ID Number 4',
      'unique_id_number5_label' => 'Unique ID Number 5',
      'is_unique_id_number1_required' => false,
      'is_unique_id_number2_required' => false,
      'is_unique_id_number3_required' => false,
      'is_unique_id_number4_required' => false,
      'is_unique_id_number5_required' => false,
    ),
    'finance' => 
    array (
      'enable_bank_code1' => true,
      'bank_code1_label' => 'Bank Code 1',
      'is_bank_code1_required' => false,
      'enable_bank_code2' => true,
      'bank_code2_label' => 'Bank Code 2',
      'is_bank_code2_required' => false,
      'enable_bank_code3' => true,
      'bank_code3_label' => 'Bank Code 3',
      'is_bank_code3_required' => false,
      'payment_number_prefix' => 'TP%YEAR_SHORT%%MONTH_NUMBER_SHORT%',
      'payment_number_digit' => 3,
      'payment_number_suffix' => '',
      'receipt_number_prefix' => 'TR%YEAR_SHORT%%MONTH_NUMBER_SHORT%',
      'receipt_number_digit' => 3,
      'receipt_number_suffix' => '',
      'transfer_number_prefix' => 'TT%YEAR_SHORT%%MONTH_NUMBER_SHORT%',
      'transfer_number_digit' => 3,
      'transfer_number_suffix' => '',
      'bank_transfer_number_prefix' => 'BT%YEAR_SHORT%%MONTH_NUMBER_SHORT%',
      'bank_transfer_number_digit' => 3,
      'bank_transfer_number_suffix' => '',
      'enable_online_transaction_number' => false,
      'online_transaction_number_prefix' => 'OT%YEAR_SHORT%%MONTH_NUMBER_SHORT%',
      'online_transaction_number_digit' => 3,
      'online_transaction_number_suffix' => '',
      'enable_paypal' => false,
      'enable_live_paypal_mode' => false,
      'paypal_client' => '',
      'paypal_secret' => '',
      'enable_stripe' => false,
      'enable_live_stripe_mode' => false,
      'stripe_client' => '',
      'stripe_secret' => '',
      'enable_payzone' => false,
      'enable_live_payzone_mode' => false,
      'payzone_merchant' => '',
      'payzone_secret_key' => '',
      'payzone_notification_key' => '',
      'enable_razorpay' => false,
      'enable_live_razorpay_mode' => false,
      'razorpay_client' => '',
      'razorpay_secret' => '',
      'enable_paystack' => false,
      'enable_live_paystack_mode' => false,
      'paystack_client' => '',
      'paystack_secret' => '',
      'enable_ccavenue' => false,
      'enable_live_ccavenue_mode' => false,
      'ccavenue_merchant' => '',
      'ccavenue_client' => '',
      'ccavenue_secret' => '',
      'enable_billdesk' => false,
      'billdesk_version' => '1.2',
      'enable_live_billdesk_mode' => false,
      'billdesk_merchant' => '',
      'billdesk_client' => '',
      'billdesk_secret' => '',
      'enable_billplz' => false,
      'enable_live_billplz_mode' => false,
      'billplz_client' => '',
      'billplz_secret' => '',
      'billplz_signature' => '',
    ),
    'reception' => 
    array (
      'enquiry_number_prefix' => 'RESM',
      'enquiry_number_digit' => 3,
      'enquiry_number_suffix' => '',
      'visitor_log_number_prefix' => 'RVLSM',
      'visitor_log_number_digit' => 3,
      'visitor_log_number_suffix' => '',
      'gate_pass_number_prefix' => 'RGPSM',
      'gate_pass_number_digit' => 3,
      'gate_pass_number_suffix' => '',
      'complaint_number_prefix' => 'RCSM',
      'complaint_number_digit' => 3,
      'complaint_number_suffix' => '',
      'query_number_prefix' => 'RQSM',
      'query_number_digit' => 3,
      'query_number_suffix' => '',
    ),
    'exam' => 
    array (
      'marksheet_format' => 'India',
      'enable_auto_lock_marks' => false,
      'auto_lock_marks_period' => 7,
    ),
    'resource' => 
    array (
      'allow_edit_diary_by_accessible_user' => false,
      'allow_delete_diary_by_accessible_user' => false,
      'allow_edit_syllabus_by_accessible_user' => false,
      'allow_delete_syllabus_by_accessible_user' => false,
      'allow_edit_lesson_plan_by_accessible_user' => false,
      'allow_delete_lesson_plan_by_accessible_user' => false,
      'online_class_use_meeting_code' => true,
      'online_class_joining_period' => 10,
      'allow_edit_online_class_by_accessible_user' => false,
      'allow_delete_online_class_by_accessible_user' => false,
      'allow_edit_assignment_by_accessible_user' => false,
      'allow_delete_assignment_by_accessible_user' => false,
      'allow_edit_learning_material_by_accessible_user' => false,
      'allow_delete_learning_material_by_accessible_user' => false,
      'enable_filter_by_assigned_subject' => false,
    ),
    'calendar' => 
    array (
      'show_celebration_in_dashboard' => true,
      'event_number_prefix' => 'CESM',
      'event_number_digit' => 3,
      'event_number_suffix' => '',
    ),
    'inventory' => 
    array (
      'stock_requisition_number_prefix' => 'ISRSM',
      'stock_requisition_number_digit' => 3,
      'stock_requisition_number_suffix' => '',
      'stock_purchase_number_prefix' => 'ISPSM',
      'stock_purchase_number_digit' => 3,
      'stock_purchase_number_suffix' => '',
      'stock_return_number_prefix' => 'ISRSM',
      'stock_return_number_digit' => 3,
      'stock_return_number_suffix' => '',
      'stock_transfer_number_prefix' => 'ISTSM',
      'stock_transfer_number_digit' => 3,
      'stock_transfer_number_suffix' => '',
      'stock_adjustment_number_prefix' => 'ISASM',
      'stock_adjustment_number_digit' => 3,
      'stock_adjustment_number_suffix' => '',
    ),
    'communication' => 
    array (
      'announcement_number_prefix' => 'CASM',
      'announcement_number_digit' => 3,
      'announcement_number_suffix' => '',
    ),
    'recruitment' => 
    array (
      'vacancy_number_prefix' => 'SMV',
      'vacancy_number_digit' => 3,
      'vacancy_number_suffix' => '',
    ),
    'transport' => 
    array (
      'show_transport_route_in_dashboard' => true,
    ),
    'mess' => 
    array (
      'show_mess_schedule_in_dashboard' => true,
    ),
    'gallery' => 
    array (
      'show_gallery_in_dashboard' => true,
      'enable_watermark' => true,
      'watermark_position' => 'top-right',
      'watermark_size' => 40,
    ),
    'library' => 
    array (
      'transaction_number_prefix' => 'SML',
      'transaction_number_digit' => 3,
      'transaction_number_suffix' => '',
    ),
    'approval' => 
    array (
      'request_number_prefix' => 'AR',
      'request_number_digit' => 3,
      'request_number_suffix' => '',
    ),
    'post' => 
    array (
      'enable_redirect_to_post_after_login' => true,
    ),
    'module' => 
    array (
    ),
    'layout' => 
    array (
      'display' => 'light',
    ),
    'print' => 
    array (
      'custom_path' => 'print.custom.',
    ),
  ),
  'cors' => 
  array (
    'paths' => 
    array (
      0 => 'api/*',
      1 => 'sanctum/csrf-cookie',
    ),
    'allowed_methods' => 
    array (
      0 => '*',
    ),
    'allowed_origins' => 
    array (
      0 => '*',
    ),
    'allowed_origins_patterns' => 
    array (
    ),
    'allowed_headers' => 
    array (
      0 => '*',
    ),
    'exposed_headers' => 
    array (
    ),
    'max_age' => 0,
    'supports_credentials' => false,
  ),
  'database' => 
  array (
    'default' => 'mysql',
    'connections' => 
    array (
      'sqlite' => 
      array (
        'driver' => 'sqlite',
        'url' => NULL,
        'database' => 'SkulSoft',
        'prefix' => '',
        'foreign_key_constraints' => true,
      ),
      'mysql' => 
      array (
        'driver' => 'mysql',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '8889',
        'database' => 'SkulSoft',
        'username' => 'root',
        'password' => 'root',
        'unix_socket' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => 'InnoDB',
        'options' => 
        array (
        ),
      ),
      'mariadb' => 
      array (
        'driver' => 'mariadb',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '8889',
        'database' => 'SkulSoft',
        'username' => 'root',
        'password' => 'root',
        'unix_socket' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => NULL,
        'options' => 
        array (
        ),
      ),
      'pgsql' => 
      array (
        'driver' => 'pgsql',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '8889',
        'database' => 'SkulSoft',
        'username' => 'root',
        'password' => 'root',
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
        'search_path' => 'public',
        'sslmode' => 'prefer',
      ),
      'sqlsrv' => 
      array (
        'driver' => 'sqlsrv',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '8889',
        'database' => 'SkulSoft',
        'username' => 'root',
        'password' => 'root',
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
      ),
    ),
    'migrations' => 'migrations',
    'redis' => 
    array (
      'client' => 'predis',
      'options' => 
      array (
        'cluster' => 'redis',
        'prefix' => 'skulsoft_database_',
      ),
      'default' => 
      array (
        'url' => NULL,
        'host' => '127.0.0.1',
        'password' => NULL,
        'port' => '6379',
        'database' => '0',
      ),
      'cache' => 
      array (
        'url' => NULL,
        'host' => '127.0.0.1',
        'password' => NULL,
        'port' => '6379',
        'database' => '1',
      ),
      'horizon' => 
      array (
        'url' => NULL,
        'host' => '127.0.0.1',
        'password' => NULL,
        'port' => '6379',
        'database' => '0',
        'options' => 
        array (
          'prefix' => 'skulsoft_horizon:',
        ),
      ),
    ),
    'type' => 'mysql',
  ),
  'debugbar' => 
  array (
    'enabled' => false,
    'except' => 
    array (
      0 => 'telescope*',
      1 => 'horizon*',
    ),
    'storage' => 
    array (
      'enabled' => true,
      'driver' => 'file',
      'path' => '/Applications/MAMP/htdocs/shulesoft/school-ms/storage/debugbar',
      'connection' => NULL,
      'provider' => '',
      'hostname' => '127.0.0.1',
      'port' => 2304,
    ),
    'include_vendors' => true,
    'capture_ajax' => true,
    'add_ajax_timing' => false,
    'error_handler' => false,
    'clockwork' => false,
    'collectors' => 
    array (
      'phpinfo' => true,
      'messages' => true,
      'time' => true,
      'memory' => true,
      'exceptions' => true,
      'log' => true,
      'db' => true,
      'views' => true,
      'route' => true,
      'auth' => false,
      'gate' => true,
      'session' => true,
      'symfony_request' => true,
      'mail' => true,
      'laravel' => false,
      'events' => false,
      'default_request' => false,
      'logs' => false,
      'files' => false,
      'config' => false,
      'cache' => false,
      'models' => true,
      'livewire' => true,
    ),
    'options' => 
    array (
      'auth' => 
      array (
        'show_name' => true,
      ),
      'db' => 
      array (
        'with_params' => true,
        'backtrace' => true,
        'backtrace_exclude_paths' => 
        array (
        ),
        'timeline' => false,
        'duration_background' => true,
        'explain' => 
        array (
          'enabled' => false,
          'types' => 
          array (
            0 => 'SELECT',
          ),
        ),
        'hints' => false,
        'show_copy' => false,
      ),
      'mail' => 
      array (
        'full_log' => false,
      ),
      'views' => 
      array (
        'timeline' => false,
        'data' => false,
      ),
      'route' => 
      array (
        'label' => true,
      ),
      'logs' => 
      array (
        'file' => NULL,
      ),
      'cache' => 
      array (
        'values' => true,
      ),
    ),
    'inject' => true,
    'route_prefix' => '_debugbar',
    'route_domain' => NULL,
    'theme' => 'auto',
    'debug_backtrace_limit' => 50,
  ),
  'excel' => 
  array (
    'exports' => 
    array (
      'chunk_size' => 1000,
      'pre_calculate_formulas' => false,
      'strict_null_comparison' => false,
      'csv' => 
      array (
        'delimiter' => ',',
        'enclosure' => '"',
        'line_ending' => '
',
        'use_bom' => false,
        'include_separator_line' => false,
        'excel_compatibility' => false,
        'output_encoding' => '',
      ),
      'properties' => 
      array (
        'creator' => '',
        'lastModifiedBy' => '',
        'title' => '',
        'description' => '',
        'subject' => '',
        'keywords' => '',
        'category' => '',
        'manager' => '',
        'company' => '',
      ),
    ),
    'imports' => 
    array (
      'read_only' => true,
      'ignore_empty' => false,
      'heading_row' => 
      array (
        'formatter' => 'slug',
      ),
      'csv' => 
      array (
        'delimiter' => ',',
        'enclosure' => '"',
        'escape_character' => '\\',
        'contiguous' => false,
        'input_encoding' => 'UTF-8',
      ),
      'properties' => 
      array (
        'creator' => '',
        'lastModifiedBy' => '',
        'title' => '',
        'description' => '',
        'subject' => '',
        'keywords' => '',
        'category' => '',
        'manager' => '',
        'company' => '',
      ),
    ),
    'extension_detector' => 
    array (
      'xlsx' => 'Xlsx',
      'xlsm' => 'Xlsx',
      'xltx' => 'Xlsx',
      'xltm' => 'Xlsx',
      'xls' => 'Xls',
      'xlt' => 'Xls',
      'ods' => 'Ods',
      'ots' => 'Ods',
      'slk' => 'Slk',
      'xml' => 'Xml',
      'gnumeric' => 'Gnumeric',
      'htm' => 'Html',
      'html' => 'Html',
      'csv' => 'Csv',
      'tsv' => 'Csv',
      'pdf' => 'Dompdf',
    ),
    'value_binder' => 
    array (
      'default' => 'Maatwebsite\\Excel\\DefaultValueBinder',
    ),
    'cache' => 
    array (
      'driver' => 'memory',
      'batch' => 
      array (
        'memory_limit' => 60000,
      ),
      'illuminate' => 
      array (
        'store' => NULL,
      ),
    ),
    'transactions' => 
    array (
      'handler' => 'db',
      'db' => 
      array (
        'connection' => NULL,
      ),
    ),
    'temporary_files' => 
    array (
      'local_path' => '/Applications/MAMP/htdocs/shulesoft/school-ms/storage/framework/cache/laravel-excel',
      'remote_disk' => NULL,
      'remote_prefix' => NULL,
      'force_resync_remote' => NULL,
    ),
  ),
  'filesystems' => 
  array (
    'default' => 'local',
    'disks' => 
    array (
      'local' => 
      array (
        'driver' => 'local',
        'root' => '/Applications/MAMP/htdocs/shulesoft/school-ms/storage/app',
      ),
      'public' => 
      array (
        'driver' => 'local',
        'root' => '/Applications/MAMP/htdocs/shulesoft/school-ms/storage/app/public',
        'url' => 'http://127.0.0.1:8002/storage',
        'visibility' => 'public',
      ),
      's3' => 
      array (
        'driver' => 's3',
        'key' => '',
        'secret' => '',
        'region' => 'us-east-1',
        'bucket' => '',
        'url' => NULL,
        'endpoint' => NULL,
        'use_path_style_endpoint' => false,
      ),
      'vol' => 
      array (
        'driver' => 'local',
        'root' => '/Applications/MAMP/htdocs/shulesoft/school-ms/storage/app',
      ),
      'r2' => 
      array (
        'driver' => 's3',
        'key' => '',
        'secret' => '',
        'region' => 'us-east-1',
        'bucket' => '',
        'url' => '',
        'visibility' => 'private',
        'endpoint' => '',
        'use_path_style_endpoint' => false,
        'throw' => true,
      ),
    ),
    'links' => 
    array (
      '/Applications/MAMP/htdocs/shulesoft/school-ms/public/storage' => '/Applications/MAMP/htdocs/shulesoft/school-ms/storage/app/public',
    ),
  ),
  'hashing' => 
  array (
    'driver' => 'bcrypt',
    'bcrypt' => 
    array (
      'rounds' => 10,
    ),
    'argon' => 
    array (
      'memory' => 65536,
      'threads' => 1,
      'time' => 4,
    ),
    'rehash_on_login' => true,
  ),
  'horizon' => 
  array (
    'domain' => NULL,
    'path' => 'horizon',
    'use' => 'default',
    'prefix' => 'skulsoft_horizon:',
    'middleware' => 
    array (
      0 => 'web',
    ),
    'waits' => 
    array (
      'redis:default' => 60,
    ),
    'trim' => 
    array (
      'recent' => 60,
      'pending' => 60,
      'completed' => 60,
      'recent_failed' => 10080,
      'failed' => 10080,
      'monitored' => 10080,
    ),
    'silenced' => 
    array (
    ),
    'metrics' => 
    array (
      'trim_snapshots' => 
      array (
        'job' => 24,
        'queue' => 24,
      ),
    ),
    'fast_termination' => false,
    'memory_limit' => 64,
    'defaults' => 
    array (
      'supervisor-1' => 
      array (
        'connection' => 'redis',
        'queue' => 
        array (
          0 => 'default',
        ),
        'balance' => 'auto',
        'maxProcesses' => 1,
        'maxTime' => 0,
        'maxJobs' => 0,
        'memory' => 128,
        'tries' => 1,
        'timeout' => 60,
        'nice' => 0,
      ),
    ),
    'environments' => 
    array (
      'production' => 
      array (
        'supervisor-1' => 
        array (
          'maxProcesses' => 10,
          'balanceMaxShift' => 1,
          'balanceCooldown' => 3,
        ),
      ),
      'local' => 
      array (
        'supervisor-1' => 
        array (
          'maxProcesses' => 3,
        ),
      ),
    ),
  ),
  'livewire' => 
  array (
    'class_namespace' => 'App\\Livewire',
    'view_path' => '/Applications/MAMP/htdocs/shulesoft/school-ms/resources/views/livewire',
    'layout' => 'components.layouts.app',
    'lazy_placeholder' => NULL,
    'temporary_file_upload' => 
    array (
      'disk' => NULL,
      'rules' => NULL,
      'directory' => NULL,
      'middleware' => NULL,
      'preview_mimes' => 
      array (
        0 => 'png',
        1 => 'gif',
        2 => 'bmp',
        3 => 'svg',
        4 => 'wav',
        5 => 'mp4',
        6 => 'mov',
        7 => 'avi',
        8 => 'wmv',
        9 => 'mp3',
        10 => 'm4a',
        11 => 'jpg',
        12 => 'jpeg',
        13 => 'mpga',
        14 => 'webp',
        15 => 'wma',
      ),
      'max_upload_time' => 5,
    ),
    'render_on_redirect' => false,
    'legacy_model_binding' => false,
    'inject_assets' => false,
    'navigate' => 
    array (
      'show_progress_bar' => true,
      'progress_bar_color' => '#2299dd',
    ),
    'inject_morph_markers' => true,
    'pagination_theme' => 'tailwind',
  ),
  'log-viewer' => 
  array (
    'enabled' => true,
    'api_only' => false,
    'require_auth_in_production' => true,
    'route_domain' => NULL,
    'route_path' => 'log-viewer',
    'back_to_system_url' => 'http://127.0.0.1:8002',
    'back_to_system_label' => NULL,
    'timezone' => NULL,
    'datetime_format' => 'Y-m-d H:i:s',
    'middleware' => 
    array (
      0 => 'web',
      1 => 'user.config',
      2 => 'auth:sanctum',
      3 => 'admin',
    ),
    'api_middleware' => 
    array (
      0 => 'Opcodes\\LogViewer\\Http\\Middleware\\EnsureFrontendRequestsAreStateful',
      1 => 'user.config',
      2 => 'auth:sanctum',
      3 => 'admin',
    ),
    'api_stateful_domains' => NULL,
    'hosts' => 
    array (
      'local' => 
      array (
        'name' => 'Local',
      ),
    ),
    'include_files' => 
    array (
      0 => '*.log',
      1 => '**/*.log',
    ),
    'exclude_files' => 
    array (
    ),
    'hide_unknown_files' => true,
    'shorter_stack_trace_excludes' => 
    array (
      0 => '/vendor/symfony/',
      1 => '/vendor/laravel/framework/',
      2 => '/vendor/barryvdh/laravel-debugbar/',
    ),
    'cache_driver' => NULL,
    'cache_key_prefix' => 'lv',
    'lazy_scan_chunk_size_in_mb' => 50,
    'strip_extracted_context' => true,
    'per_page_options' => 
    array (
      0 => 10,
      1 => 25,
      2 => 50,
      3 => 100,
      4 => 250,
      5 => 500,
    ),
    'defaults' => 
    array (
      'use_local_storage' => true,
      'folder_sorting_method' => 'ModifiedTime',
      'folder_sorting_order' => 'desc',
      'log_sorting_order' => 'desc',
      'per_page' => 25,
      'theme' => 'System',
      'shorter_stack_traces' => false,
    ),
    'root_folder_prefix' => 'root',
  ),
  'logging' => 
  array (
    'default' => 'stack',
    'deprecations' => NULL,
    'channels' => 
    array (
      'stack' => 
      array (
        'driver' => 'stack',
        'channels' => 
        array (
          0 => 'single',
        ),
        'ignore_exceptions' => false,
      ),
      'single' => 
      array (
        'driver' => 'single',
        'path' => '/Applications/MAMP/htdocs/shulesoft/school-ms/storage/logs/laravel.log',
        'level' => 'debug',
      ),
      'daily' => 
      array (
        'driver' => 'daily',
        'path' => '/Applications/MAMP/htdocs/shulesoft/school-ms/storage/logs/laravel.log',
        'level' => 'debug',
        'days' => 14,
      ),
      'slack' => 
      array (
        'driver' => 'slack',
        'url' => NULL,
        'username' => 'Laravel Log',
        'emoji' => ':boom:',
        'level' => 'debug',
      ),
      'papertrail' => 
      array (
        'driver' => 'monolog',
        'level' => 'debug',
        'handler' => 'Monolog\\Handler\\SyslogUdpHandler',
        'handler_with' => 
        array (
          'host' => NULL,
          'port' => NULL,
          'connectionString' => 'tls://:',
        ),
      ),
      'stderr' => 
      array (
        'driver' => 'monolog',
        'level' => 'debug',
        'handler' => 'Monolog\\Handler\\StreamHandler',
        'formatter' => NULL,
        'with' => 
        array (
          'stream' => 'php://stderr',
        ),
      ),
      'syslog' => 
      array (
        'driver' => 'syslog',
        'level' => 'debug',
      ),
      'errorlog' => 
      array (
        'driver' => 'errorlog',
        'level' => 'debug',
      ),
      'null' => 
      array (
        'driver' => 'monolog',
        'handler' => 'Monolog\\Handler\\NullHandler',
      ),
      'emergency' => 
      array (
        'path' => '/Applications/MAMP/htdocs/shulesoft/school-ms/storage/logs/laravel.log',
      ),
    ),
  ),
  'mail' => 
  array (
    'default' => 'log',
    'mailers' => 
    array (
      'smtp' => 
      array (
        'transport' => 'smtp',
        'host' => 'mailhog',
        'port' => '1025',
        'encryption' => NULL,
        'username' => NULL,
        'password' => NULL,
        'timeout' => NULL,
        'scheme' => 'smtp',
      ),
      'ses' => 
      array (
        'transport' => 'ses',
      ),
      'postmark' => 
      array (
        'transport' => 'postmark',
      ),
      'resend' => 
      array (
        'transport' => 'resend',
      ),
      'sendmail' => 
      array (
        'transport' => 'sendmail',
        'path' => '/usr/sbin/sendmail -t -i',
      ),
      'log' => 
      array (
        'transport' => 'log',
        'channel' => NULL,
      ),
      'array' => 
      array (
        'transport' => 'array',
      ),
      'failover' => 
      array (
        'transport' => 'failover',
        'mailers' => 
        array (
          0 => 'smtp',
          1 => 'log',
        ),
      ),
      'roundrobin' => 
      array (
        'transport' => 'roundrobin',
        'mailers' => 
        array (
          0 => 'ses',
          1 => 'postmark',
        ),
      ),
      'mailgun' => 
      array (
        'transport' => 'mailgun',
      ),
    ),
    'from' => 
    array (
      'address' => 'hello@example.com',
      'name' => 'SkulSoft',
    ),
    'markdown' => 
    array (
      'theme' => 'default',
      'paths' => 
      array (
        0 => '/Applications/MAMP/htdocs/shulesoft/school-ms/resources/views/vendor/mail',
      ),
    ),
  ),
  'permission' => 
  array (
    'models' => 
    array (
      'permission' => 'Spatie\\Permission\\Models\\Permission',
      'role' => 'Spatie\\Permission\\Models\\Role',
    ),
    'table_names' => 
    array (
      'roles' => 'roles',
      'permissions' => 'permissions',
      'model_has_permissions' => 'model_has_permissions',
      'model_has_roles' => 'model_has_roles',
      'role_has_permissions' => 'role_has_permissions',
    ),
    'column_names' => 
    array (
      'role_pivot_key' => NULL,
      'permission_pivot_key' => NULL,
      'model_morph_key' => 'model_id',
      'team_foreign_key' => 'team_id',
    ),
    'register_permission_check_method' => true,
    'register_octane_reset_listener' => false,
    'events_enabled' => false,
    'teams' => true,
    'team_resolver' => 'Spatie\\Permission\\DefaultTeamResolver',
    'use_passport_client_credentials' => false,
    'display_permission_in_exception' => false,
    'display_role_in_exception' => false,
    'enable_wildcard_permission' => false,
    'cache' => 
    array (
      'expiration_time' => 
      \DateInterval::__set_state(array(
         'from_string' => true,
         'date_string' => '24 hours',
      )),
      'key' => 'spatie.permission.cache',
      'store' => 'default',
    ),
  ),
  'purifier' => 
  array (
    'encoding' => 'UTF-8',
    'finalize' => true,
    'ignoreNonStrings' => false,
    'cachePath' => '/Applications/MAMP/htdocs/shulesoft/school-ms/storage/app/purifier',
    'cacheFileMode' => 493,
    'settings' => 
    array (
      'default' => 
      array (
        'HTML.Doctype' => 'HTML 4.01 Transitional',
        'HTML.Allowed' => 'div[class],b[class],strong[class],i[class],em[class],u[class],a[href|title|class],ul[class],ol[class],li[class],p[style|class],br,span[style|class],img[width|height|alt|src|class]',
        'CSS.AllowedProperties' => 'font,font-size,font-weight,font-style,font-family,text-decoration,padding-left,color,background-color,text-align',
        'AutoFormat.AutoParagraph' => true,
        'AutoFormat.RemoveEmpty' => true,
        'Output.Newline' => '',
        'HTML.TidyLevel' => 'none',
      ),
      'test' => 
      array (
        'Attr.EnableID' => 'true',
      ),
      'youtube' => 
      array (
        'HTML.SafeIframe' => 'true',
        'URI.SafeIframeRegexp' => '%^(http://|https://|//)(www.youtube.com/embed/|player.vimeo.com/video/)%',
      ),
      'custom_definition' => 
      array (
        'id' => 'html5-definitions',
        'rev' => 1,
        'debug' => false,
        'elements' => 
        array (
          0 => 
          array (
            0 => 'section',
            1 => 'Block',
            2 => 'Flow',
            3 => 'Common',
          ),
          1 => 
          array (
            0 => 'nav',
            1 => 'Block',
            2 => 'Flow',
            3 => 'Common',
          ),
          2 => 
          array (
            0 => 'article',
            1 => 'Block',
            2 => 'Flow',
            3 => 'Common',
          ),
          3 => 
          array (
            0 => 'aside',
            1 => 'Block',
            2 => 'Flow',
            3 => 'Common',
          ),
          4 => 
          array (
            0 => 'header',
            1 => 'Block',
            2 => 'Flow',
            3 => 'Common',
          ),
          5 => 
          array (
            0 => 'footer',
            1 => 'Block',
            2 => 'Flow',
            3 => 'Common',
          ),
          6 => 
          array (
            0 => 'address',
            1 => 'Block',
            2 => 'Flow',
            3 => 'Common',
          ),
          7 => 
          array (
            0 => 'hgroup',
            1 => 'Block',
            2 => 'Required: h1 | h2 | h3 | h4 | h5 | h6',
            3 => 'Common',
          ),
          8 => 
          array (
            0 => 'figure',
            1 => 'Block',
            2 => 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow',
            3 => 'Common',
          ),
          9 => 
          array (
            0 => 'figcaption',
            1 => 'Inline',
            2 => 'Flow',
            3 => 'Common',
          ),
          10 => 
          array (
            0 => 'video',
            1 => 'Block',
            2 => 'Optional: (source, Flow) | (Flow, source) | Flow',
            3 => 'Common',
            4 => 
            array (
              'src' => 'URI',
              'type' => 'Text',
              'width' => 'Length',
              'height' => 'Length',
              'poster' => 'URI',
              'preload' => 'Enum#auto,metadata,none',
              'controls' => 'Bool',
            ),
          ),
          11 => 
          array (
            0 => 'source',
            1 => 'Block',
            2 => 'Flow',
            3 => 'Common',
            4 => 
            array (
              'src' => 'URI',
              'type' => 'Text',
            ),
          ),
          12 => 
          array (
            0 => 's',
            1 => 'Inline',
            2 => 'Inline',
            3 => 'Common',
          ),
          13 => 
          array (
            0 => 'var',
            1 => 'Inline',
            2 => 'Inline',
            3 => 'Common',
          ),
          14 => 
          array (
            0 => 'sub',
            1 => 'Inline',
            2 => 'Inline',
            3 => 'Common',
          ),
          15 => 
          array (
            0 => 'sup',
            1 => 'Inline',
            2 => 'Inline',
            3 => 'Common',
          ),
          16 => 
          array (
            0 => 'mark',
            1 => 'Inline',
            2 => 'Inline',
            3 => 'Common',
          ),
          17 => 
          array (
            0 => 'wbr',
            1 => 'Inline',
            2 => 'Empty',
            3 => 'Core',
          ),
          18 => 
          array (
            0 => 'ins',
            1 => 'Block',
            2 => 'Flow',
            3 => 'Common',
            4 => 
            array (
              'cite' => 'URI',
              'datetime' => 'CDATA',
            ),
          ),
          19 => 
          array (
            0 => 'del',
            1 => 'Block',
            2 => 'Flow',
            3 => 'Common',
            4 => 
            array (
              'cite' => 'URI',
              'datetime' => 'CDATA',
            ),
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            0 => 'iframe',
            1 => 'allowfullscreen',
            2 => 'Bool',
          ),
          1 => 
          array (
            0 => 'table',
            1 => 'height',
            2 => 'Text',
          ),
          2 => 
          array (
            0 => 'td',
            1 => 'border',
            2 => 'Text',
          ),
          3 => 
          array (
            0 => 'th',
            1 => 'border',
            2 => 'Text',
          ),
          4 => 
          array (
            0 => 'tr',
            1 => 'width',
            2 => 'Text',
          ),
          5 => 
          array (
            0 => 'tr',
            1 => 'height',
            2 => 'Text',
          ),
          6 => 
          array (
            0 => 'tr',
            1 => 'border',
            2 => 'Text',
          ),
        ),
      ),
      'custom_attributes' => 
      array (
        0 => 
        array (
          0 => 'a',
          1 => 'target',
          2 => 'Enum#_blank,_self,_target,_top',
        ),
      ),
      'custom_elements' => 
      array (
        0 => 
        array (
          0 => 'u',
          1 => 'Inline',
          2 => 'Inline',
          3 => 'Common',
        ),
      ),
    ),
  ),
  'queue' => 
  array (
    'default' => 'sync',
    'connections' => 
    array (
      'sync' => 
      array (
        'driver' => 'sync',
      ),
      'database' => 
      array (
        'driver' => 'database',
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90,
        'after_commit' => false,
      ),
      'beanstalkd' => 
      array (
        'driver' => 'beanstalkd',
        'host' => 'localhost',
        'queue' => 'default',
        'retry_after' => 90,
        'block_for' => 0,
        'after_commit' => false,
      ),
      'sqs' => 
      array (
        'driver' => 'sqs',
        'key' => '',
        'secret' => '',
        'prefix' => 'https://sqs.us-east-1.amazonaws.com/your-account-id',
        'queue' => 'default',
        'suffix' => NULL,
        'region' => 'us-east-1',
        'after_commit' => false,
      ),
      'redis' => 
      array (
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'default',
        'retry_after' => 90,
        'block_for' => NULL,
        'after_commit' => false,
      ),
    ),
    'batching' => 
    array (
      'database' => 'mysql',
      'table' => 'job_batches',
    ),
    'failed' => 
    array (
      'driver' => 'database-uuids',
      'database' => 'mysql',
      'table' => 'failed_jobs',
    ),
  ),
  'sanctum' => 
  array (
    'stateful' => 
    array (
      0 => 'localhost',
      1 => '127.0.0.1',
      2 => '127.0.0.1:8002',
      3 => 'localhost:8002',
    ),
    'guard' => 
    array (
      0 => 'web',
    ),
    'expiration' => NULL,
    'token_prefix' => '',
    'middleware' => 
    array (
      'authenticate_session' => 'Laravel\\Sanctum\\Http\\Middleware\\AuthenticateSession',
      'encrypt_cookies' => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
      'validate_csrf_token' => 'Illuminate\\Foundation\\Http\\Middleware\\ValidateCsrfToken',
    ),
  ),
  'services' => 
  array (
    'postmark' => 
    array (
      'token' => NULL,
    ),
    'ses' => 
    array (
      'key' => '',
      'secret' => '',
      'region' => 'us-east-1',
    ),
    'resend' => 
    array (
      'key' => NULL,
    ),
    'slack' => 
    array (
      'notifications' => 
      array (
        'bot_user_oauth_token' => NULL,
        'channel' => NULL,
      ),
    ),
    'mailgun' => 
    array (
      'domain' => NULL,
      'secret' => NULL,
      'endpoint' => 'api.mailgun.net',
    ),
    'google' => 
    array (
      'client_id' => NULL,
      'client_secret' => NULL,
      'redirect' => NULL,
    ),
    'facebook' => 
    array (
      'client_id' => NULL,
      'client_secret' => NULL,
      'redirect' => NULL,
    ),
    'twitter' => 
    array (
      'client_id' => NULL,
      'client_secret' => NULL,
      'redirect' => NULL,
    ),
    'github' => 
    array (
      'client_id' => NULL,
      'client_secret' => NULL,
      'redirect' => NULL,
    ),
    'microsoft' => 
    array (
      'client_id' => NULL,
      'client_secret' => NULL,
      'redirect' => NULL,
    ),
  ),
  'session' => 
  array (
    'driver' => 'file',
    'lifetime' => '120',
    'expire_on_close' => false,
    'encrypt' => false,
    'files' => '/Applications/MAMP/htdocs/shulesoft/school-ms/storage/framework/sessions',
    'connection' => NULL,
    'table' => 'sessions',
    'store' => NULL,
    'lottery' => 
    array (
      0 => 2,
      1 => 100,
    ),
    'cookie' => 'skulsoft_session',
    'path' => '/',
    'domain' => NULL,
    'secure' => NULL,
    'http_only' => true,
    'same_site' => 'lax',
    'partitioned' => false,
  ),
  'view' => 
  array (
    'paths' => 
    array (
      0 => '/Applications/MAMP/htdocs/shulesoft/school-ms/resources/views',
    ),
    'compiled' => '/Applications/MAMP/htdocs/shulesoft/school-ms/storage/framework/views',
  ),
  'concurrency' => 
  array (
    'default' => 'process',
  ),
  'image' => 
  array (
    'driver' => 'gd',
  ),
  'tinker' => 
  array (
    'commands' => 
    array (
    ),
    'alias' => 
    array (
    ),
    'dont_alias' => 
    array (
      0 => 'App\\Nova',
    ),
  ),
);
