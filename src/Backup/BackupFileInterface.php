<?php

namespace App\Backup;

interface BackupFileInterface
{

    public function getName():string;
    public function getPath():string;
    public function getId(): string;
    public function getCreatedAt(): \DateTime;
    public function getSize(): int;
}