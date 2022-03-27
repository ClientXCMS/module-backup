<?php
return [
    "cron.schedules" => \DI\add(\App\Backup\BackupSchedule::class),
    "admin.menu.items" => \DI\add(\DI\get(\App\Backup\BackupAdminItem::class)),
    'permissions.list' => \DI\add([
        'backup.delete' => 'Delete backup',
        'backup.download' => 'Download backup',
        'backup' => 'Show backups',
        'backup.create' => 'Create manually backup',
    ])
];