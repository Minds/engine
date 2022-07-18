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
        $referrer = null; // guid or username

        // always prefer the referrer in the param to the cookie we already have
        if (isset($params['referrer'])) {
            $referrer = $params['referrer'];
        } elseif (!isset($cookies['referrer']) && $this->entity) {
            switch (get_class($this->entity)) {
                case User::class:
                    $referrer = $this->entity->getGuid();
                    break;
                default:
                    $referrer = $this->entity->getOwnerGuid();
            }
        }

        if ($referrer) {
            $cookie = new Cookie();
            $cookie
                ->setName('referrer')
                ->setValue($referrer)
                ->setExpire(time() + (60 * 60 * 24)) //valid for 24 hours
                ->setPath('/')
                ->create();
            $_COOKIE['referrer'] = $referrer; // TODO: replace with Response object later
        }
    }
}
