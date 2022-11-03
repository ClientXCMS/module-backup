<?php

namespace App\Backup\Actions;

use App\Admin\Database\SettingTable;
use App\Admin\Entity\Setting;
use App\Backup\GoogleDriveApi;
use ClientX\Response\RedirectResponse;
use ClientX\Session\FlashService;
use Psr\Http\Message\ServerRequestInterface;

class GoogleAction extends \ClientX\Actions\Action
{
    private SettingTable $settingTable;
    private FlashService $service;
    private GoogleDriveApi $driveApi;

    public function  __construct(SettingTable $settingTable, FlashService $service, GoogleDriveApi $driveApi)
    {
        $this->settingTable = $settingTable;
        $this->service = $service;
        $this->driveApi = $driveApi;
    }

    public function __invoke(ServerRequestInterface $request)
    {
        $code = $request->getQueryParams()['code'] ?? null;
        if ($code == null || empty($code)){
            die("Code cannot be null");
        }
        $setting = new Setting();
        $setting->setSettingKey("backup_google_access_token");
        $code = $this->driveApi->getAccessToken($code);
        $setting->setSettingValue(json_encode($code));
        $this->settingTable->saveSettings([$setting], ['backup_google_access_token' => json_encode($code)]);
        $this->service->success('Done!');
        return new RedirectResponse('/admin/backup');
    }
}