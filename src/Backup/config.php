<?php

use App\Backup\BackupAdminItem;
use App\Backup\BackupSchedule;
use App\Backup\BackupService;
use App\Backup\BackupSettings;
use App\Backup\GoogleDriveApi;
use App\Backup\Services\File\FileServiceBackup;
use App\Backup\Services\Google\GoogleDriveServiceBackup;
use function ClientX\setting;
use function DI\add;
use function DI\autowire;
use function DI\get;

return [
    "cron.schedules" => add(BackupSchedule::class),
    "admin.menu.items" => add(get(BackupAdminItem::class)),
    'permissions.list' => add([
        'backup.delete' => 'Delete backup',
        'backup.download' => 'Download backup',
        'backup' => 'Show backups',
        'backup.create' => 'Create manually backup',
    ]),
    'admin.settings' => add(get(BackupSettings::class)),
    BackupSettings::class => autowire()->constructorParameter('types', get('backup.types')),
    BackupService::class => autowire()->constructorParameter('types', get('backup.types'))->constructorParameter('limit', setting('max_backup_keeping', '4')),
    BackupSchedule::class => autowire()->constructorParameter('hour', setting('hour_between_backup', '12')),
    GoogleDriveApi::class => autowire()
        ->constructorParameter('client_secret', $_ENV['GOOGLE_SECRET'] ?? '')
        ->constructorParameter('folder_id', $_ENV['GOOGLE_FOLDER_ID'] ?? '')
        ->constructorParameter('client_id', $_ENV['GOOGLE_CLIENT_ID'] ?? ''),
    'backup.types' => [get(GoogleDriveServiceBackup::class), get(FileServiceBackup::class)],
];