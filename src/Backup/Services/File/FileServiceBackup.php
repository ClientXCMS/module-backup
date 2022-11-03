<?php


namespace App\Backup\Services\File;

use App\Backup\BackupServiceInterface;
use ClientX\App;
use ClientX\Response\FileResponse;
use PDO;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class FileServiceBackup implements BackupServiceInterface
{

    private PDO $PDO;
    private string $suffix;
    private Filesystem $file;

    const PREFIX = "db_backup_";
    private Finder $finder;
    private string $directory;

    public function __construct(PDO $PDO)
    {
        $this->PDO = $PDO;
        $this->suffix = date('Y-m-d_H-i-s');
        $this->file = new Filesystem();
        $this->finder = new Finder();
        $this->directory = App::getAppDir() . '/backups';
    }

    public function fetch(): array
    {
        if (file_exists($this->directory) == false) {
            mkdir($this->directory, 777);
        }
        $tmp = [];
        $files = $this->finder->in($this->directory)->files();
        foreach ($files as $file) {
            $tmp[] = new BackupFile($file);
        }
        usort($tmp, function (BackupFile $a, BackupFile $b) {
            try {
                return $a->getCreatedAt()->format('U') < $b->getCreatedAt()->format('U');
            } catch (\Exception $e) {
                return false;
            }
        });
        return $tmp;
    }

    public function download(string $id)
    {
        try {
            /** @var \SplFileInfo $file */
            $file = collect($this->finder->in($this->directory)->filter(function (\SplFileInfo  $file) use ($id) {
                return $file->getFilename() === $id . '.sql';
            })->files())->first();
        } catch (IOException $e) {
            return $e->getMessage();
        }
        return new FileResponse(200, [], $file->getRealPath(), "ClientXCMS Database backup of " . (new BackupFile($file))->getCreatedAt()->format('d-m-y H-i'));
    }

    public function delete(string $id)
    {
        try {
            $this->file->remove($this->directory .'/' . $id . '.sql');
            return true;
        } catch (IOException $e) {
            return $e->getMessage();
        }
    }


    public function save(string $output)
    {
        $filename = self::PREFIX . $this->suffix . '.sql';
        $this->file->appendToFile($this->directory . '/' . $filename, $output);
        return true;
    }

    public function type()
    {
        return "file";
    }

    public function name()
    {
        return "File";
    }

    public function detected(): bool
    {
        return is_writable(dirname($this->directory));
    }

    public function help()
    {
        $directory = $this->directory;
        return "check if PHP has write permissions on $directory";
    }
}
