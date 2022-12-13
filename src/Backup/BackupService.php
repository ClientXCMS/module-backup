<?php

namespace App\Backup;


use ClientX\App;

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
        $dumper = (new BackupDumper());
        $settings = [
            \PDO::ATTR_PERSISTENT => true,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false
        ];
        foreach ($settings as $k => $v){
            $this->pdo->setAttribute($k, $v);
        }
        $dumper->start(App::getTmpDir() . '/_dump.sql', $this->pdo);
        $content = file_get_contents(App::getTmpDir() . '/_dump.sql');
        unlink(App::getTmpDir() . '/_dump.sql');
        return $content;

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
        return collect($this->types)->filter(function (BackupServiceInterface $backupService) {
            return $backupService->detected();
        })->mapWithKeys(function (BackupServiceInterface $service) {
            return [$service->type() => $service->fetch()];
        });
    }

    public function download(string $id, string $type)
    {
        /** @var BackupServiceInterface|null $service */
        $service = collect($this->types)->filter(function (BackupServiceInterface $backupService) {
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
        $service = collect($this->types)->filter(function (BackupServiceInterface $backupService) {
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
        return collect($this->types)->filter(function (BackupServiceInterface $backupService) {
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
