<?php

namespace App\Backup;

use App\Admin\Database\SettingTable;
use App\Admin\Entity\Setting;
use ClientX\Router;
use Google\Service\Drive;
use GuzzleHttp\Psr7\Response;

class GoogleDriveApi
{
    const SCOPE = 'https://www.googleapis.com/auth/drive';
    private Router $router;
    private \Google\Client $client;
    private SettingTable $settingTable;
    private string $folder_id;

    public function __construct(Router $router, string $client_id, string $client_secret, string $folder_id,  SettingTable $settingTable)
    {
        $this->router = $router;
        $this->client = new \Google\Client();
        $this->client->setClientSecret($client_secret);
        $this->client->setRedirectUri($this->getRedirectUri());
        $this->client->setClientId($client_id);
        $this->client->setScopes(self::SCOPE);

        $this->client->setAccessType('offline');
        $this->client->setApprovalPrompt('force');
        $this->settingTable = $settingTable;
        if ($this->settingTable->findSetting("backup_google_access_token") != null) {

            $this->client->setAccessToken($this->settingTable->findSetting("backup_google_access_token"));
        }
        $this->folder_id = $folder_id;
    }

    public function getAccessToken(string $code)
    {
        return $this->client->fetchAccessTokenWithAuthCode($code);
    }

    public function refreshToken(): array
    {

        $refreshToken = $this->settingTable->findSetting("backup_google_access_token");
        $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
        return $this->client->getAccessToken();
    }

    public function getOauth2Link():string
    {
        return $this->client->createAuthUrl();
    }

    public function getRedirectUri()
    {
        return $this->router->generateURIAbsolute('backup.google');
    }

    public function fetchFolder()
    {
        $this->ensureAccessToken();
        $service = new Drive($this->client);
        $folderId = $this->folder_id;
        return $service->files->listFiles(array(
            'pageSize' => 25,
            'fields' => "nextPageToken, files(contentHints/thumbnail,fileExtension,iconLink,id,name,size,thumbnailLink,webContentLink,webViewLink,mimeType,parents, createdTime)",
            'q' => "'".$folderId."' in parents"
        ))->getFiles();
    }

    protected function ensureAccessToken()
    {
        if ($this->client->isAccessTokenExpired()) {
            $refresh = $this->refreshToken();
            $this->client->setAccessToken(json_encode($refresh));
            $setting = new Setting();
            $setting->setSettingKey("backup_google_access_token");
            $setting->setSettingValue(json_encode($refresh));
            $this->settingTable->saveSettings([$setting], ['backup_google_access_token' => json_encode($refresh)]);
        }
    }

    public function downloadFile(string $id): Response
    {

        $this->ensureAccessToken();
        $service = new Drive($this->client);
        $filename = $service->files->get($id)->getName();
        return new Response(200, [
            'Content-Disposition' => 'attachment;filename="'. $filename .'";',
            'Content-Type' => 'text/plain',
        ], $service->files->get($id, ['alt' => 'media'])->getBody()->__toString());
    }

    public function deleteFile(string $id): bool
    {
        $this->ensureAccessToken();
        $service = new Drive($this->client);
        $service->files->delete($id);
        return true;
    }

    public function upload(string $currentFile): Drive\DriveFile
    {
        $this->ensureAccessToken();
        $currentFileInfo = pathinfo($currentFile);
        $currentFileMime = mime_content_type($currentFile);
        $service = new Drive($this->client);
        $file = new Drive\DriveFile();
        $file->setName($currentFileInfo['basename']);
        $file->setDescription("Backup made by CLIENTXCMS module");
        $file->setMimeType($currentFileMime);
        $file->setParents([$this->folder_id]);
        return $service->files->create($file, [
            'data' => file_get_contents($currentFile),
            'mimeType' => $currentFileMime,
            'uploadType'=> 'multipart'
        ]);
    }

}
