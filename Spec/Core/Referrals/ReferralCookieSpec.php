<?php

namespace Spec\Minds\Core\Referrals;

use Minds\Core\Referrals\ReferralCookie;
use Minds\Entities\User;
use Minds\Entities\Activity;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Uri;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ReferralCookieSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(ReferralCookie::class);
    }

    public function it_should_set_a_referral_cookie_from_a_referral_param()
    {
        $request = (new ServerRequest())->withQueryParams(['referrer' => 'mark']);
        $this->withRouterRequest($request);
        $this->create();

        expect($_COOKIE['referrer'])
            ->toBe('mark');
    }

    public function it_should_not_set_cookie_if_already_present()
    {
        $_COOKIE['referrer'] = 'bill';
        $request = (new ServerRequest())
            ->withCookieParams(['referrer' => 'mark']);
        $this->withRouterRequest($request);
        $this->create();

        expect($_COOKIE['referrer'])
            ->toBe('bill');
    }

    public function it_should_set_cookie_from_user_entity()
    {
        $user = new User();
        $user->guid = 123;

        $request = (new ServerRequest());
        $this->withRouterRequest($request);
        $this->setEntity($user);
        $this->create();

        expect($_COOKIE['referrer'])
            ->toBe('123');
    }

    public function it_should_set_cookie_from_activity()
    {
        $activity = new Activity();
        $activity->guid = 123;
        $activity->owner_guid = 456;

        $request = (new ServerRequest());
        $this->withRouterRequest($request);
        $this->setEntity($activity);
        $this->create();

        expect($_COOKIE['referrer'])
            ->toBe('456');
    }
    
    public function it_should_not_allow_entity_to_override_param()
    {
        $activity = new Activity();
        $activity->guid = 123;
        $activity->owner_guid = 456;

        $request = (new ServerRequest())
            ->withQueryParams(['referrer' => 'mark']);
        ;
        $this->withRouterRequest($request);
        $this->setEntity($activity);
        $this->create();

        expect($_COOKIE['referrer'])
            ->toBe('mark');
    }

    public function it_should_not_allow_entity_to_override_cookie()
    {
        $activity = new Activity();
        $activity->guid = 123;
        $activity->owner_guid = 456;

        $request = (new ServerRequest())
            ->withCookieParams(['referrer' => 'mark']);
        ;
        $this->withRouterRequest($request);
        $this->setEntity($activity);
        $this->create();

        expect($_COOKIE['referrer'])
            ->toBe('mark');
    }
    
    public function it_should_prefer_param_to_cookie()
    {
        $_COOKIE['referrer'] = 'bill';

        $request = (new ServerRequest())
            ->withQueryParams(['referrer' => 'mark'])
            ->withCookieParams(['referrer' => 'bill']);
        ;
        $this->withRouterRequest($request);
        $this->create();

        expect($_COOKIE['referrer'])
            ->toBe('mark');
    }
}
