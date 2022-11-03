<?php


namespace App\Backup\Actions;

use App\Backup\BackupService;
use ClientX\Actions\Action;
use ClientX\Middleware\CsrfMiddleware;
use ClientX\Renderer\RendererInterface;
use ClientX\Response\RedirectBackResponse;
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

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return \ClientX\Response\RedirectBackResponse|string
     */
    public function __invoke(ServerRequestInterface $request)
    {
        if ($request->getMethod() === 'DELETE') {
            return $this->delete($request);
        }

        if ($request->getMethod() === 'PUT') {
            return $this->create($request);
        }

        if ($request->getMethod() === 'POST') {
            return $this->download($request);
        }
        $fetch = $this->service->fetchAll();
        $elements = $this->service->getNames();
        return $this->render('@backup/index', compact('fetch', 'elements'));
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return \ClientX\Response\RedirectBackResponse
     */
    private function delete(ServerRequestInterface $request): RedirectBackResponse
    {
        $result = $this->service->delete($request->getAttribute('id'), $request->getAttribute('type'));
        if (is_string($result)) {
            $this->error($result);
        } else {
            $this->success('Done!');
        }
        return $this->back($request);
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return \ClientX\Response\RedirectBackResponse
     */
    private function create(ServerRequestInterface $request): RedirectBackResponse
    {
        $dump = $this->service->dump();
        $result = $this->service->save($dump, array_keys($this->service->getNames())[0]);
        if ($result) {
            $this->success("Done!");
        }
        return $this->back($request);

    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     */
    private function download(ServerRequestInterface $request)
    {

        $result = $this->service->download($request->getAttribute('id'), $request->getAttribute('type'));
        if ($result) {
            $this->success("Done!");
            return $result;
        }
        return $this->back($request);

    }
}
