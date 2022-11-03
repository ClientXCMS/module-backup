<?php


namespace App\Backup\Services\File;

use App\Backup\BackupFileInterface;
use SplFileInfo;

class BackupFile implements BackupFileInterface
{

    private SplFileInfo $info;

    public function __construct(SplFileInfo $splFileInfo)
    {
        $this->info = $splFileInfo;
    }

    public function getName(): string
    {
        return $this->info->getBasename();
    }

    public function getPath(): string
    {
        return $this->info->getPath() . '/' . $this->getName();
    }

    public function getId(): string
    {
        return $this->info->getFilenameWithoutExtension();
    }

    /**
     * @throws \Exception
     */
    public function getCreatedAt(): \DateTime
    {
        $name = $this->info->getFilenameWithoutExtension();
        $name = str_replace(FileServiceBackup::PREFIX, '', $name);
        $parts = explode('_', $name);
        [$year, $month, $day] = explode('-', $parts[1]);
        [$hour, $minute, $second] = explode('-', $parts[0]);
        $entity = new \DateTime();
        $entity->setDate($hour, $minute, $second);
        $entity->setTime($year, $month, $day);
        return $entity;
    }

    public function getSize(): int
    {
        return $this->info->getSize() / 1000;
    }
}
