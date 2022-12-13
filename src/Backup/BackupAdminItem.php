<?php


namespace App\Backup;

use ClientX\Navigation\NavigationItemInterface;
use ClientX\Renderer\RendererInterface;

class BackupAdminItem implements NavigationItemInterface
{

    public function getPosition(): int
    {
        return 10;
    }

    public function render(RendererInterface $renderer): string
    {
        return $renderer->render("@backup/menu");
    }
}
