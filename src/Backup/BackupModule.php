<?php


namespace App\Backup;

use App\Backup\Actions\BackupAction;
use App\Backup\Actions\GoogleAction;
use ClientX\Renderer\RendererInterface;
use ClientX\Router;
use Psr\Container\ContainerInterface;

class BackupModule extends \ClientX\Module
{

    const DEFINITIONS = __DIR__ . '/config.php';

    const TRANSLATIONS = [
        "fr_FR" => __DIR__ . "/trans/fr.php",
        "en_GB" => __DIR__ . "/trans/en.php",
        "es_ES" => __DIR__ . "/trans/en.php"
    ];

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __construct(RendererInterface $renderer, Router $router, ContainerInterface $container)
    {
        $renderer->addPath('backup', __DIR__ . '/views');
        if ($container->has("admin.prefix")) {
            $prefix = $container->get("admin.prefix");
            $router->any($prefix . '/backup', BackupAction::class, 'backup');
            $router->get($prefix . '/backup/google', GoogleAction::class, 'backup.google');
            $router->put($prefix . '/backup/add', BackupAction::class, 'backup.create');
            $router->post($prefix . '/backup/download/[*:type]/[*:id]', BackupAction::class, 'backup.download');
            $router->delete($prefix . '/backup/delete/[*:type]/[*:id]', BackupAction::class, 'backup.delete');
        }
        //dd($container->get('backup.types'));

    }
}
