<?php


namespace App\Backup\Services\Google;

use App\Backup\BackupFileInterface;
use Google\Service\Drive\DriveFile;

class GoogleDriveFile implements BackupFileInterface
{

    private DriveFile $driveFile;

    public function __construct(DriveFile $driveFile)
    {
        $this->driveFile = $driveFile;
    }

    public function getName(): string
    {
        return $this->driveFile->getName();
    }

    public function getPath(): string
    {
        return $this->driveFile->getName();

    }

    public function getId(): string
    {
        return $this->driveFile->getId();
    }

    /**
     * @throws \Exception
     */
    public function getCreatedAt(): \DateTime
    {
        return (new \DateTime($this->driveFile->getCreatedTime()));
    }

    public function getSize(): int
    {
        return $this->driveFile->getSize() / 1000;
    }
}
