<?php

namespace Spec\Minds\Core\Provisioner;

use Minds\Core\Config\Config;
use Minds\Core\Minds;
use Minds\Entities\Site;
use PhpSpec\ObjectBehavior;

class InstallerSpec extends ObjectBehavior
{
    private $configMock;

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Provisioner\Installer');
    }

    public function let(Minds $minds, Config $config)
    {
        $this->beConstructedWith($config);
        $this->configMock = $config;

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

        $config->site_name = 'PHPSpec Minds';
        $config->set('site_url', 'https://phpspec.minds.io/');
    }

    public function it_should_check_options_valid()
    {
        $this
            ->shouldNotThrow('Minds\\Exceptions\\ProvisionException')
            ->duringCheckOptions();
    }

    /*function it_should_check_options_invalid_domain()
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
    }*/

    /*function it_should_check_options_invalid_username()
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
    }*/

    /*function it_should_check_options_invalid_password()
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
    }*/

    /*function it_should_check_options_invalid_email()
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
    }*/

    public function it_should_build_config()
    {
        $this
            ->shouldNotThrow('\\ProvisionException')
            ->duringBuildConfig([ 'returnResult' => true ]);
    }

    public function it_should_setup_site(Site $site)
    {
        $this->configMock->get('site_name')->willReturn('PHPSpec Minds');
        $site->set('name', 'PHPSpec Minds')->shouldBeCalled();

        $this->configMock->get('site_url')->willReturn('https://phpspec.minds.io/');
        $site->set('url', 'https://phpspec.minds.io/')->shouldBeCalled();

        $site->set('access_id', 2)->shouldBeCalled();
        $site->set('email', 'phpspec@minds.io')->shouldBeCalled();

        $site->save()->willReturn(true)->shouldBeCalled();

        $this
            ->shouldNotThrow('\\ProvisionException')
            ->duringSetupSite($site);
    }

    public function it_should_get_site_url()
    {
        $this->configMock->get('site_url')->willReturn('https://phpspec.minds.io/');

        $this->getSiteUrl()->shouldReturn('https://phpspec.minds.io/');
    }
}
