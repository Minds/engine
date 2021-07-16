<?php

namespace Minds\Core\Email;

use Exception;
use Minds\Core\Analytics\Metrics\Event;
use Minds\Core\Di\Di;
use Minds\Core\Email\Confirmation\Manager as ConfirmationManager;
use Minds\Core\Session;

class RouterHooks
{
    /** @var Event */
    protected $event;

    /** @var ConfirmationManager */
    protected $confirmationManager;

    /**
     * RouterHooks constructor.
     * @param Event $event
     * @param ConfirmationManager $confirmationManager
     */
    public function __construct($event = null, $confirmationManager = null)
    {
        $this->event = $event ?: new Event();
        $this->confirmationManager = $confirmationManager ?: Di::_()->get('Email\Confirmation');
    }

    public function withRouterRequest($request)
    {
        $queryParams = $request->getQueryParams();
        $path = $request->getUri()->getPath();
        $action = 'email:clicks';
        if (strpos($path, '/emails/unsubscribe') !== false) {
            $action = 'email:unsubscribe';
        } elseif (isset($queryParams['__e_cnf_token'])) {
            $cnfToken = rtrim($queryParams['__e_cnf_token'], '?');
            try {
                $this->confirmationManager
                    ->confirm($cnfToken);
            } catch (Exception $e) {
                // Do not continue processing.
                // TODO: Log?
                return;
            }

            $action = 'email:confirm';
        }
        $platform = isset($queryParams['cb']) ? 'mobile' : 'browser';
        if (isset($queryParams['platform'])) {
            $platform = $queryParams['platform'];
        }
        if (isset($queryParams['__e_ct_guid'])) {
            $userGuid = $queryParams['__e_ct_guid'];
            $emailCampaign = $queryParams['campaign'] ?? 'unknown';
            $emailTopic = $queryParams['topic'] ?? 'unknown';
            $emailState = $queryParams['state'] ?? 'unknown';

            $this->event->setType('action')
                ->setAction($action)
                ->setProduct('platform')
                ->setUserGuid($userGuid)
                ->setPlatform($platform)
                ->setEmailCampaign($emailCampaign)
                ->setEmailTopic($emailTopic)
                ->setEmailState($emailState);

            $this->event->push();
        }
    }
}
