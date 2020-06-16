<?php

namespace Spec\Minds\Entities;

use Minds\Entities\User;
use Minds\Core\Di\Di;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Minds\Common\ChannelMode;

class UserSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(User::class);
    }

    public function it_should_not_return_admin_if_not_whitelisted()
    {
        //remove ip whitelist check
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '10.56.0.10';

        $this->admin = 'yes';
        $this->isAdmin()->shouldBe(false);
        //Di::_()->get('Config')->set('admin_ip_whitelist', [ '10.56.0.1' ]);
    }

    public function it_should_return_admin_if_whitelisted()
    {
        //remove ip whitelist check
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '10.56.0.1';
        Di::_()->get('Config')->set('admin_ip_whitelist', ['10.56.0.1']);

        $this->admin = 'yes';
        $this->isAdmin()->shouldBe(true);
    }

    public function it_should_assign_the_onchain_booster_status()
    {
        $this->setOnchainBooster(123);
        $this->getOnchainBooster()->shouldReturn(123);
    }

    public function it_should_recognise_a_user_is_in_the_onchain_booster_timeframe()
    {
        $this->setOnchainBooster(20601923579999);
        $this->isOnchainBooster()->shouldReturn(true);
    }

    public function it_should_recognise_a_user_is_not_in_the_onchain_booster_timeframe()
    {
        $this->setOnchainBooster(1560192357);
        $this->isOnchainBooster()->shouldReturn(false);
    }

    public function it_should_have_a_default_mode_of_open()
    {
        $this->getMode()->shouldEqual(ChannelMode::OPEN);
    }

    public function it_should_assign_channel_modes()
    {
        $this->setMode(ChannelMode::CLOSED);
        $this->getMode()->shouldEqual(ChannelMode::CLOSED);
        $this->setMode(ChannelMode::MODERATED);
        $this->getMode()->shouldEqual(ChannelMode::MODERATED);
    }

    public function it_should_have_write_permissions_if_its_an_old_user()
    {
        $this->setEmailConfirmationToken('');
        $this->setEmailConfirmedAt(null);
        $this->isTrusted()->shouldReturn(true);
    }

    public function it_should_have_write_permissions_if_email_is_verified()
    {
        $this->setEmailConfirmedAt(time());
        $this->isTrusted()->shouldReturn(true);
    }

    public function it_should_not_have_write_permissions_if_its_a_new_user_with_unverified_email()
    {
        $this->setEmailConfirmationToken('token');
        $this->setEmailConfirmedAt(0);

        $this->isTrusted()->shouldReturn(false);
    }

    public function it_should_export_values()
    {
        $export = $this->export()->getWrappedObject();
        expect($export['mode'])->shouldEqual(ChannelMode::OPEN);
    }

    public function it_should_get_surge_token()
    {
        $token = '11111';
        $this->surge_token = $token;
        $this->getSurgeToken()->shouldReturn($token);
    }

    public function it_should_set_surge_token()
    {
        $token = '11111';
        $this->setSurgeToken($token)->shouldReturnAnInstanceOf('Minds\Entities\User');
        $this->getSurgeToken()->shouldReturn($token);
    }

    public function it_should_add_single_hashtag()
    {
        $hashtag = 'minds';
        
        $this->addHashtag($hashtag)
            ->shouldReturnAnInstanceOf('Minds\Entities\User');
        
        $this->getHashtags()[0]->shouldReturn($hashtag);
    }

    public function it_should_remove_single_hashtag()
    {
        $hashtag = 'minds';
        
        $this->addHashtag($hashtag);
        
        $this->removeHashtag($hashtag)
            ->shouldReturnAnInstanceOf('Minds\Entities\User');
        
        $this->getHashtags()->shouldEqual([]);
    }
}
