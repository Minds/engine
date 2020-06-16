<?php
namespace Minds\Core\DismissibleWidgets;

use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response\JsonResponse;
use Minds\Api\Exportable;

class Controllers
{
    /** @var Manager */
    protected $manager;

    public function __construct($manager = null)
    {
        $this->manager = $manager ?? new Manager();
    }

    /**
     * Put request for a widget
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function putWidget(ServerRequest $request): JsonResponse
    {
        $parameters = $request->getAttribute('parameters');
        if (!($parameters['id'] ?? null)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => ':id not provided'
            ]);
        }

        try {
            $this->manager->setDimissedId($parameters['id']);
        } catch (InvalidWidgetIDException $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Invalid ID provided'
            ]);
        }

        return new JsonResponse([
            'status' => 'success',
        ]);
    }
}
