<?php

namespace App\Backup;


class BackupService
{

    private array $types;
    private int $limit;
    private \PDO $pdo;

    public function __construct(array $types, string $limit, \PDO $pdo)
    {
        $this->types = $types;
        $this->limit = (int)$limit;
        $this->pdo = $pdo;
    }

    public function dump($tables = '*')
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
            $query = $this->pdo->prepare('SHOW TABLES');
            $query->execute();
            while ($row = $query->fetch(\PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            $query->closeCursor();
        } else {
            $tables = is_array($tables) ? $tables : explode(',', $tables);
        }

        foreach ($tables as $table) {
            $query = $this->pdo->prepare("SELECT * FROM `$table`");
            $query->execute();
            $output .= "DROP TABLE IF EXISTS `$table`;" . PHP_EOL;

            $query2 = $this->pdo->prepare("SHOW CREATE TABLE `$table`");
            $query2->execute();
            $row2 = $query2->fetch(\PDO::FETCH_NUM);
            $query2->closeCursor();
            $output .= PHP_EOL . $row2[1] . ";" . PHP_EOL;

            while ($row = $query->fetch(\PDO::FETCH_NUM)) {
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
        return $output;
    }

    public function save(string $dump, string $type)
    {

        /** @var BackupServiceInterface|null $service */
        $service = collect($this->types)->filter(function (BackupServiceInterface $service) use ($type) {
            return $service->type() == $type && $service->detected();
        })->first();
        if ($service == null) {
            return "Cannot find service";
        }
        return $service->save($dump);
    }

    public function deleteOld():array
    {

        return collect($this->types)->filter(function (BackupServiceInterface $backupService) {
            return $backupService->detected() && count($backupService->fetch()) > $this->limit;
        })->mapWithKeys(function (BackupServiceInterface $backupService) {
            $deleted = collect($backupService->fetch())->slice($this->limit);
            $tmp = collect($deleted)->map(function(BackupFileInterface $file) use ($backupService){
                try {
                    $backupService->delete($file->getId());
                    return 'success';
                } catch (\Exception $e){
                    return $e->getMessage();
                }
            })->toArray();
            return [$backupService->type() => $tmp];
        })->toArray();
    }

    public function fetchAll()
    {
        return collect($this->types)->filter(function (BackupServiceInterface $backupService) use ($dump) {
            return $backupService->detected();
        })->mapWithKeys(function (BackupServiceInterface $service) {
            return [$service->type() => $service->fetch()];
        });
    }

    public function download(string $id, string $type)
    {
        /** @var BackupServiceInterface|null $service */
        $service = collect($this->types)->filter(function (BackupServiceInterface $backupService) use ($dump) {
            return $backupService->detected();
        })->filter(function (BackupServiceInterface $service) use ($type) {
            return $service->type() == $type;
        })->first();
        if ($service == null) {
            return "Cannot find service";
        }
        return $service->download($id);
    }

    public function delete(string $id, string $type)
    {

        /** @var BackupServiceInterface|null $service */
        $service = collect($this->types)->filter(function (BackupServiceInterface $backupService) use ($dump) {
            return $backupService->detected();
        })->filter(function (BackupServiceInterface $service) use ($type) {
            return $service->type() == $type;
        })->first();
        if ($service == null) {
            return "Cannot find service";
        }
        return $service->delete($id);
    }

    public function getNames():array
    {
        return collect($this->types)->filter(function (BackupServiceInterface $backupService) use ($dump) {
            return $backupService->detected();
        })->mapWithKeys(function ($type) {
            return [$type->type() => $type->name()];
        })->toArray();
    }

    public function backupAll():array
    {
        $dump = $this->dump();
        return collect($this->types)->filter(function (BackupServiceInterface $backupService) use ($dump) {
            return $backupService->detected();
        })->mapWithKeys(function (BackupServiceInterface $backupService) use ($dump) {
            return [$backupService->type() => $backupService->save($dump)];
        })->toArray();
    }
}
