<?php
return [
    "cron.schedules" => \DI\add(\App\Backup\BackupSchedule::class),
    "admin.menu.items" => \DI\add(\DI\get(\App\Backup\BackupAdminItem::class))
];