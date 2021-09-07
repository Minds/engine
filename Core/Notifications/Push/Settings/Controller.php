<?php
namespace Minds\Core\Notifications\Push\Settings;

use Minds\Entities\User;
use Minds\Api\Exportable;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * Controls a users push configurations
 * @package Minds\Core\Notifications\Push\Settings
 */
class Controller
{
    /** @var Manager */
    protected $manager;

    /**
     * Controller constructor.
     * @param null $manager
     */
    public function __construct(
        $manager = null
    ) {
        $this->manager = $manager ?? new Manager();
    }

    /**
     * Returns count of unread notifications
     * @return JsonResponse
     *
     */
    public function getSettings(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        $opts = new SettingsListOpts();
        $opts->setUserGuid($user->getGuid());
    
        $settings = $this->manager->getList($opts);
        
        return new JsonResponse([
            'status' => 'success',
            'settings' => Exportable::_($settings),
        ], 200);
    }

    /**
     * Returns count of unread notifications
     * @return JsonResponse
     *
     */
    public function toggle(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        $notificationGroup = $request->getAttribute('parameters')['notificationGroup'] ?? null;

        $params = $request->getParsedBody();

        $pushSetting = new PushSetting();
        $pushSetting->setUserGuid($user->getGuid())
            ->setNotificationGroup($notificationGroup)
            ->setEnabled($params['enabled'] ?? true);

        if ($this->manager->add($pushSetting)) {
            return new JsonResponse($pushSetting->export(), 200);
        } else {
            return new JsonResponse([], 400);
        }
    }
}
