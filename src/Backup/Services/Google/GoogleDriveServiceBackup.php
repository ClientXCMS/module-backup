<?php

namespace App\Backup\Services\Google;

use App\Backup\BackupServiceInterface;
use App\Backup\GoogleDriveApi;
use App\Backup\Services\File\BackupFile;
use ClientX\App;
use Google\Service\Drive\DriveFile;
use Symfony\Component\Filesystem\Filesystem;

class GoogleDriveServiceBackup implements BackupServiceInterface
{

    private GoogleDriveApi $driveApi;
    private Filesystem $filesystem;

    public function __construct(GoogleDriveApi $driveApi, Filesystem $filesystem)
    {
        $this->driveApi = $driveApi;
        $this->filesystem = $filesystem;
    }

    public function save(string $output)
    {

        $filename = App::getTmpDir() . '/'. date('Y-m-d_H-i-s') . '.sql';
        $this->filesystem->appendToFile($filename, $output);
        $this->driveApi->upload($filename);
        $this->filesystem->remove($filename);
        return 'true';
    }

    public function type()
    {
        return "googledrive";
    }

    public function name()
    {
        return "Google Drive";
    }
    public function delete(string $id)
    {
        return $this->driveApi->deleteFile($id);
    }

    public function download(string $id)
    {
        return $this->driveApi->downloadFile($id);
    }

    public function fetch(): array
    {
        $tmp =  collect($this->driveApi->fetchFolder())->map(function(DriveFile $file){
            return new GoogleDriveFile($file);
        })->toArray();

        usort($tmp, function ($a,$b) {
            try {
                return $a->getCreatedAt()->format('U') < $b->getCreatedAt()->format('U');
            } catch (\Exception $e) {
                return false;
            }
        });
        return $tmp;
    }

    public function detected(): bool
    {
        return !empty($_ENV['GOOGLE_CLIENT_ID']) && !empty($_ENV['GOOGLE_SECRET']);
    }

    public function help()
    {
        return "check if GOOGLE_CLIENT_ID, GOOGLE_SECRET and GOOGLE_FOLDER_ID keys are in .env file";
    }
}