<?php


namespace App\Backup;

use ClientX\App;
use ClientX\Response\FileResponse;
use PDO;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupService
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


    public function backup($tables = '*')
    {
        $output = "-- database backup - " . date('Y-m-d H:i:s') . PHP_EOL;
        $output .= "SET NAMES utf8;" . PHP_EOL;
        $output .= "SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';" . PHP_EOL;
        $output .= "SET foreign_key_checks = 0;" . PHP_EOL;
        $output .= "SET AUTOCOMMIT = 0;" . PHP_EOL;
        $output .= "START TRANSACTION;" . PHP_EOL;
        //get all table names
        if ($tables == '*') {
            $tables = [];
            $query = $this->PDO->prepare('SHOW TABLES');
            $query->execute();
            while ($row = $query->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            $query->closeCursor();
        } else {
            $tables = is_array($tables) ? $tables : explode(',', $tables);
        }

        foreach ($tables as $table) {
            $query = $this->PDO->prepare("SELECT * FROM `$table`");
            $query->execute();
            $output .= "DROP TABLE IF EXISTS `$table`;" . PHP_EOL;

            $query2 = $this->PDO->prepare("SHOW CREATE TABLE `$table`");
            $query2->execute();
            $row2 = $query2->fetch(PDO::FETCH_NUM);
            $query2->closeCursor();
            $output .= PHP_EOL . $row2[1] . ";" . PHP_EOL;

            while ($row = $query->fetch(PDO::FETCH_NUM)) {
                $output .= "INSERT INTO `$table` VALUES(";
                for ($j = 0; $j < count($row); $j++) {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = str_replace("\n", "\\n", $row[$j]);
                    if (isset($row[$j])) {
                        $output .= "'" . $row[$j] . "'";
                    } else {
                        $output .= "''";
                    }
                    if ($j < (count($row) - 1)) {
                        $output .= ',';
                    }
                }
                $output .= ");" . PHP_EOL;
            }
        }
        $output .= PHP_EOL . PHP_EOL;

        $output .= "COMMIT;";

        $filename = self::PREFIX . $this->suffix . '.sql';

        $this->file->appendToFile($this->directory . '/' . $filename, $output);
        return true;
    }
}
