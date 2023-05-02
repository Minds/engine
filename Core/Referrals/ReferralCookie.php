<?php
/**
 * Referral Cookie wrapper.
 */
namespace Minds\Core\Referrals;

use Minds\Common\Cookie;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Zend\Diactoros\ServerRequest;

class ReferralCookie
{
    private const COOKIE_NAME = "referrer";

    /** @var Request */
    private $request;

    /** @var int window of validity for cookie (set exp to time() + self::VALIDITY_WINDOW). */
    const VALIDITY_WINDOW = 259200; // 3 days.

    public function __construct(
        private ?Cookie $cookie = null,
        private ?EntitiesBuilder $entitiesBuilder = null
    ) {
        $this->cookie ??= new Cookie();
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
    }

    /**
     * Set the router request and return a new cloned instance.
     * @param Request $request - request to create with and derive potential cookie value from.
     * @return ReferralCookie new cloned instance of $this.
     */
    public function withRouterRequest(ServerRequest $request): ReferralCookie
    {
        $referralCookie = clone $this;
        $referralCookie->request = $request;
        return $referralCookie;
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

        $params = $this->request->getQueryParams();

        if (isset($params['referrer'])) {
            $this->cookie->setName(self::COOKIE_NAME)
                ->setValue($params['referrer'])
                ->setExpire(time() + self::VALIDITY_WINDOW)
                ->setPath('/')
                ->create();
        }
    }

    public function getAffiliateGuid(): ?int
    {
        $request = $this->request;
        $affiliateUser = isset($request->getCookieParams()[self::COOKIE_NAME]) ? $this->entitiesBuilder->getByUserByIndex($request->getCookieParams()[self::COOKIE_NAME]) : null;
        return (int) $affiliateUser?->getGuid() ?? null;
    }
}
