<?php
namespace Minds\Core\Blockchain\SKALE;

use Minds\Core\Di\Di;
use Minds\Core\Features;
use Exception;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

class Controller
{
    /** @var Manager */
    protected $manager;

    /** @var Features\Manager */
    protected $featuresManager;

    /**
     * Controller constructor.
     * @param null $manager
     */
    public function __construct(
        $manager = null,
        $featuresManager = null
    ) {
        $this->manager = $manager ?? new Manager();
        $this->featuresManager = $featuresManager ?? Di::_()->get('Features\Manager');
    }

    /**
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function canExit(ServerRequest $request): JsonResponse
    {
        // $receiver = $request->getQueryParams()['receiver'] ?? '';

        // if (!$receiver) {
        //     throw new UserErrorException('Missing receiver parameter');
        // }
        
        // TODO: remove and uncomment above;
        $receiver = '0xb4Ea99EA800E5f59fBA5e342aA3a1A07cB59A074';

        $canExit = $this->manager->canExit($receiver);

        return new JsonResponse(array_merge([
            // 'status' => 'success',
            'canExit' => $canExit
        ]));
    }
}
