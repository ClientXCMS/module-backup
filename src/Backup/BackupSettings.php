<?php

namespace App\Backup;

use App\Admin\Settings\SettingsInterface;
use ClientX\App;
use ClientX\Renderer\RendererInterface;
use ClientX\Validator;

class BackupSettings implements SettingsInterface
{

    private GoogleDriveApi $driveApi;
    private array $types;

    public function __construct(GoogleDriveApi $driveApi, array $types)
    {
        $this->driveApi = $driveApi;
        $this->types = $types;
    }


    public function name(): string
    {
        return "backup";
    }

    public function title(): string
    {
        return "Backup";
    }

    public function icon(): string
    {
        return "fas fa-save";
    }

    public function render(RendererInterface $renderer)
    {
        if(array_key_exists('GOOGLE_SECRET', $_ENV) && array_key_exists('GOOGLE_CLIENT_ID', $_ENV)){
            if (!empty($_ENV['GOOGLE_CLIENT_ID']) && !empty($_ENV['GOOGLE_SECRET'])){
                $gdriveLink = $this->driveApi->getOauth2Link();
            } else {
                $gdriveLink = null;
            }
        } else{
            $gdriveLink = null;
        }
        $detected = collect($this->types)->filter(function(BackupServiceInterface $backupService) { return $backupService->detected();})->toArray();
        return $renderer->render("@backup/settings", ['gdrivelink' => $gdriveLink, 'types' => $this->types, 'detected' => $detected]);
    }

    public function validate(array $params): Validator
    {
        return (new Validator($params))->notEmpty('max_backup_keeping', 'hour_between_backup');
    }
}