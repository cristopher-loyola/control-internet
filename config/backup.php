<?php

return [
    'backup' => [
        'name' => 'control-internet',

        'source' => [
            'files' => [
                'include' => [
                    storage_path('app/public'),
                    public_path('images'),
                ],
                'exclude' => [
                    storage_path('app/backups'),
                    storage_path('logs'),
                    storage_path('framework'),
                    base_path('node_modules'),
                    base_path('.git'),
                    base_path('vendor'),
                ],
                'relative_path' => null,
                'follow_links' => false,
                'ignore_unreadable_directories' => false,
            ],

            'databases' => [
                'mysql',
            ],
        ],

        'destination' => [
            'filename_prefix' => 'backup_',
            'disks' => ['local'],
        ],

        'temporary_directory' => storage_path('app/backup-temp'),
    ],

    'notifications' => [
        'notifications' => [
            \Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification::class => [],
            \Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification::class => [],
            \Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification::class => [],
            \Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification::class => ['mail'],
        ],
        'notifiable' => \Spatie\Backup\Notifications\Notifiable::class,
        'mail' => [
            'to' => env('BACKUP_EMAIL', env('MAIL_FROM_ADDRESS')),
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS'),
                'name' => env('MAIL_FROM_NAME'),
            ],
        ],
        'slack' => [
            'webhook_url' => '',
            'channel' => null,
            'username' => null,
            'icon' => null,
        ],
        'discord' => [
            'webhook_url' => '',
            'username' => null,
            'avatar_url' => null,
        ],
    ],

    'database_dump_generator' => [
        'mysql' => [
            'dump_binary_path' => 'C:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysqldump.exe',
            'dump_command_options' => [
                '--routines',
                '--triggers',
                '--events',
                '--single-transaction',
                '--create-options',
                '--complete-insert',
                '--disable-keys',
                '--extended-insert',
                '--hex-blob',
            ],
        ],
    ],

    'cleanup' => [
        'strategy' => \Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy::class,
        'default_strategy' => [
            'keep_all_backups_for_days' => 15,
            'keep_daily_backups_for_days' => 0,
            'keep_weekly_backups_for_weeks' => 0,
            'keep_monthly_backups_for_months' => 0,
            'keep_yearly_backups_for_years' => 0,
            'delete_oldest_backups_when_using_more_megabytes_than' => 5000,
        ],
    ],
];
