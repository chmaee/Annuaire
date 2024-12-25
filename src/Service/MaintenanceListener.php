<?php

namespace App\Service;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;

class MaintenanceListener
{
    private $maintenanceMode;
    private $router;

    public function __construct(bool $maintenanceMode, RouterInterface $router)
    {
        $this->maintenanceMode = $maintenanceMode;
        $this->router = $router;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Si la route actuelle est celle de la maintenance, on n'effectue pas la redirection
        if ($request->attributes->get('_route') === 'app_maintenance_page') {
            return;
        }

        // Redirection vers la page de maintenance si le site est en mode maintenance
        if ($this->maintenanceMode) {
            $maintenanceUrl = $this->router->generate('app_maintenance_page');
            $response = new RedirectResponse($maintenanceUrl);
            $event->setResponse($response);
        }
    }
}
