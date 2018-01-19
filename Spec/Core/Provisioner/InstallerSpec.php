<?php

namespace Spec\Minds\Core\Provisioner;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use Minds\Core\Minds;
use Minds\Entities\Site;

class InstallerSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Provisioner\Installer');
    }

    function let(Minds $minds) {
        global $CONFIG;

        $this->setApp($minds);

        $this->setOptions([
            'overwrite-settings' => true,
            'domain' => 'phpspec.minds.io',
            'username' => 'phpspec',
            'password' => 'phpspec1',
            'email' => 'phpspec@minds.io',
            'site-name' => 'PHPSpec Minds',
            'site-email' => 'phpspec@minds.io',
            'cassandra-server' => '127.0.0.1',
            'elasticsearch-server' => 'http://localhost',
            'email-public-key' => __FILE__,
            'email-private-key' => __FILE__,
            'phone-number-public-key' => __FILE__,
            'phone-number-private-key' => __FILE__
        ]);

        $CONFIG->site_name = 'PHPSpec Minds';
    }

    function it_should_check_options_valid()
    {
        $this
            ->shouldNotThrow('Minds\\Exceptions\\ProvisionException')
            ->duringCheckOptions();
    }

    function it_should_check_options_invalid_domain()
    {
        $this->setOptions([
            'username' => 'phpspec',
            'password' => 'phpspec1',
            'email' => 'phpspec@minds.io',
        ]);

        $this
            ->shouldThrow('Minds\\Exceptions\\ProvisionException')
            ->duringCheckOptions();

        $this->setOptions([
            'domain' => '!@#!$asdasd%!%.com!',
            'username' => 'phpspec',
            'password' => 'phpspec1',
            'email' => 'phpspec@minds.io',
        ]);

        $this
            ->shouldThrow('Minds\\Exceptions\\ProvisionException')
            ->duringCheckOptions();
    }

    function it_should_check_options_invalid_username()
    {
        $this->setOptions([
            'domain' => 'phpspec.minds.io',
            'password' => 'phpspec1',
            'email' => 'phpspec@minds.io',
        ]);

        $this
            ->shouldThrow('Minds\\Exceptions\\ProvisionException')
            ->duringCheckOptions();
        
        $this->setOptions([
            'domain' => 'phpspec.minds.io',
            'username' => 'foo.bar$',
            'password' => 'phpspec1',
            'email' => 'phpspec@minds.io',
        ]);

        $this
            ->shouldThrow('Minds\\Exceptions\\ProvisionException')
            ->duringCheckOptions();
    }

    function it_should_check_options_invalid_password()
    {
        $this->setOptions([
            'domain' => 'phpspec.minds.io',
            'username' => 'phpspec',
            'email' => 'phpspec@minds.io',
        ]);

        $this
            ->shouldThrow('Minds\\Exceptions\\ProvisionException')
            ->duringCheckOptions();

        $this->setOptions([
            'domain' => 'phpspec.minds.io',
            'username' => 'phpspec',
            'password' => '000',
            'email' => 'phpspec@minds.io',
        ]);

        $this
            ->shouldThrow('Minds\\Exceptions\\ProvisionException')
            ->duringCheckOptions();
    }

    function it_should_check_options_invalid_email()
    {
        $this->setOptions([
            'domain' => 'phpspec.minds.io',
            'username' => 'phpspec',
            'password' => 'phpspec1',
        ]);

        $this
            ->shouldThrow('Minds\\Exceptions\\ProvisionException')
            ->duringCheckOptions();

        $this->setOptions([
            'domain' => 'phpspec.minds.io',
            'username' => 'phpspec',
            'password' => 'phpspec1',
            'email' => 'asldkj!@#!@#...net)',
        ]);

        $this
            ->shouldThrow('Minds\\Exceptions\\ProvisionException')
            ->duringCheckOptions();
    }

    function it_should_build_config()
    {
        $this
            ->shouldNotThrow('\\ProvisionException')
            ->duringBuildConfig([ 'returnResult' => true ]);
    }

    function it_should_setup_site(Site $site)
    {
        $site->set('name', 'PHPSpec Minds')->shouldBeCalled();
        $site->set('url', 'https://phpspec.minds.io/')->shouldBeCalled();
        $site->set('access_id', 2)->shouldBeCalled();
        $site->set('email', 'phpspec@minds.io')->shouldBeCalled();

        $site->save()->willReturn(true)->shouldBeCalled();

        $this
            ->shouldNotThrow('\\ProvisionException')
            ->duringSetupSite($site);
    }

    function it_should_get_site_url()
    {
        $this->getSiteUrl()->shouldReturn('https://phpspec.minds.io/');
    }
}
