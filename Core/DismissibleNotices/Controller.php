<?php
namespace Minds\Core\DismissibleNotices;

use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response\JsonResponse;
use Minds\Core\Di\Di;

/**
 * DismissibleNotices Controller.
 */
class Controller
{
    public function __construct(private ?Manager $manager = null)
    {
        $this->manager = $manager ?? Di::_()->get('DismissibleNotices\Manager');
    }

    /**
     * Set a notice as dismissed via manager.
     * @param ServerRequest $request - server request object.
     * @return JsonResponse - JSON response.
     */
    public function dismissNotice(ServerRequest $request): JsonResponse
    {
        $parameters = $request->getAttribute('parameters');

        if (!($parameters['id'] ?? null)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => ':id not provided'
            ]);
        }

        try {
            $this->manager->setDismissed($parameters['id']);
        } catch (UserErrorException $e) {
            throw $e;
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Invalid Notice ID provided'
            ]);
        }

        return new JsonResponse([
            'status' => 'success',
        ]);
    }
}
