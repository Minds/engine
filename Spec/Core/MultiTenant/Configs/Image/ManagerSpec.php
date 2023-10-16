<?php

namespace Spec\Minds\Core\MultiTenant\Configs\Image;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Media\Imagick\Manager as ImagickManager;
use Minds\Core\MultiTenant\Configs\Enums\MultiTenantConfigImageType;
use Minds\Core\MultiTenant\Configs\Image\Manager;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class ManagerSpec extends ObjectBehavior
{
    private Collaborator $imagickManager;
    private Collaborator $config;

    public function let(
        ImagickManager $imagickManager,
        Config $config,
    ) {
        $this->beConstructedWith($imagickManager, $config);
        $this->imagickManager = $imagickManager;
        $this->config = $config;

        Di::_()->bind('Storage\S3', function ($di) {
            return new class {
                public function __construct()
                {
                }
                public function write()
                {
                    return $this;
                }
                public function open()
                {
                    return $this;
                }
                public function close()
                {
                    return $this;
                }
            };
        });
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_get_tenant_owner_guid()
    {
        $tenantOwnerGuid = 1234567890123456;

        $this->config->get('tenant_owner_guid')
            ->shouldBeCalled()
            ->willReturn($tenantOwnerGuid);

        $this->getTenantOwnerGuid()
            ->shouldBe($tenantOwnerGuid);
    }
}
