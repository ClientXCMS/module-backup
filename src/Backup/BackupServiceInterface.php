<?php

namespace App\Backup;

interface BackupServiceInterface
{

    public function save(string $output);

    public function type();
    public function name();
    public function help();

    public function delete(string $id);

    public function download(string $id);

    public function fetch():array;

    public function detected():bool;
}