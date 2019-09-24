<?php
/**
 * Referral Cookie
 */
namespace Minds\Core\Referrals;

use Minds\Entities\User;
use Minds\Common\Cookie;
use Zend\Diactoros\ServerRequest;

class ReferralCookie
{
    /** @var Request */
    private $request;

    /** @var Entity */
    private $entity;

    /**
     * Set the router request
     * @param Request $request
     * @param Response $response
     * @return $this
     */
    public function withRouterRequest(ServerRequest $request): ReferralCookie
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Set Entity
     * @param Entity|User $entity
     * @return $this
     */
    public function setEntity($entity): ReferralCookie
    {
        $this->entity = $entity;
        return $this;
    }

    /**
     * Set the referral cookie
     * @return void
     */
    public function create(): void
    {
        if (!$this->request) {
            return;
        }

        $cookies = $this->request->getCookieParams();
        $params = $this->request->getQueryParams();

        if (isset($cookies['referrer'])) {
            return; // Do not override previosuly set cookie
        }

        $referrerGuid = null;

        if (isset($params['referrer'])) { // Is a referrer param set in the request?
            $referrerGuid = $params['referrer'];
        } elseif ($this->entity) { // Was an entity set?
            switch (get_class($this->entity)) {
                case User::class:
                    $referrerGuid = $this->entity->getGuid();
                    break;
                default:
                    $referrerGuid = $this->entity->getOwnerGuid();
            }
        }

        if ($referrerGuid) {
            $cookie = new Cookie();
            $cookie
                ->setName('referrer')
                ->setValue($referrerGuid)
                ->setExpire(time() + (60 * 60 * 24)) //valid for 24 hours
                ->setPath('/')
                ->create();
            $_COOKIE['referrer'] = $referrerGuid; // TODO: replace with Response object later
        }
    }
}
