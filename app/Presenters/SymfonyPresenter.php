<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Kernel;
use Nette\Application;
use Nette\Application\UI\Presenter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Runtime\SymfonyRuntime;

class SymfonyPresenter extends Presenter
{
    private Kernel $kernel;
    private Response $response;
    private Request $request;

    public function actionDefault(): void
    {
        $runtime = new SymfonyRuntime(['project_dir' => __DIR__.'/../../',]);
        [$app, $args] = $runtime->getResolver(static function (array $context) {
            return new Kernel($context['APP_ENV'], (bool)$context['APP_DEBUG']);
        })->resolve();
        $this->kernel = $app(...$args);
        $this->request = Request::createFromGlobals();
        $this->response = $this->kernel->handle($this->request);
    }

    public function renderDefault(): void
    {
        $response = $this->getHttpResponse();
        foreach ($this->response->headers->all() as $name => $item) {
            $response->setHeader($name, implode(' ', $item) . ' ' . ($response->getHeader($name) ?? ''));
        }
        $response->setCode($this->response->getStatusCode());
        $this->sendResponse(new Application\Responses\CallbackResponse(function () {
            $this->response->sendContent();
        }));
    }

    public function shutdown(Application\Response $response): void
    {
        $this->kernel->terminate($this->request, $this->response);
    }
}
