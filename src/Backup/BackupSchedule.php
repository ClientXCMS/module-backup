<?php


namespace App\Backup;

use Carbon\Carbon;
use ClientX\Cron\AbstractCron;

class BackupSchedule extends AbstractCron
{


    protected $name = "backupdb";
    protected $title = "Backup Database";
    protected $icon = "fas fa-save";
    public $time = 43200;
    private BackupService $service;

    public function __construct(BackupService $service)
    {
        $this->service = $service;
    }

    public function run(): array
    {
        return [
            $this->service->backup()
        ];
    }

}
