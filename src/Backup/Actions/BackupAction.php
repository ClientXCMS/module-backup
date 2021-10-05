<?php


namespace App\Backup\Actions;

use App\Backup\BackupService;
use ClientX\Actions\Action;
use ClientX\Middleware\CsrfMiddleware;
use ClientX\Renderer\RendererInterface;
use ClientX\Session\FlashService;
use Psr\Http\Message\ServerRequestInterface;

class BackupAction extends Action
{

    private BackupService $service;
    private CsrfMiddleware $csrf;

    public function __construct(BackupService $service, RendererInterface $renderer, FlashService $flash, CsrfMiddleware $csrf)
    {
        $this->service = $service;
        $this->renderer = $renderer;
        $this->flash = $flash;
        $this->csrf = $csrf;
    }

    public function __invoke(ServerRequestInterface $request)
    {

        if ($request->getMethod() === 'DELETE') {
            return $this->delete($request);
        }

        if ($request->getMethod() === 'POST') {
            return $this->restore($request);
        }
        $saves = $this->service->fetch();
        return $this->render('@backup/index', compact('saves'));
    }

    private function restore(ServerRequestInterface $request)
    {
        $result = $this->service->restore($request->getAttribute('save'));
        if (is_string($result)) {
            $this->error($result);
        }
        return $this->json(['csrf' => $this->csrf->generateToken(), 'result' => true]);
    }

    private function delete(ServerRequestInterface $request)
    {
        $result = $this->service->delete($request->getParsedBody()['id']);
        if (is_string($result)) {
            $this->error($result);
        } else {
            $this->success('Done!');
        }
        return $this->back($request);
    }
}
