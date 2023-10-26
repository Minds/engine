<?php

namespace Spec\Minds\Core\MultiTenant\Configs\Image;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Media\Imagick\Manager as ImagickManager;
use Minds\Core\MultiTenant\Configs\Image\Manager;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class ManagerSpec extends ObjectBehavior
{
    private Collaborator $imagickManager;
    private Collaborator $config;
    private Collaborator $multiTenantBootServiceMock;

    public function let(
        ImagickManager $imagickManager,
        Config $config,
        MultiTenantBootService $multiTenantBootServiceMock,
    ) {
        $this->beConstructedWith($imagickManager, $config, $multiTenantBootServiceMock);
        $this->imagickManager = $imagickManager;
        $this->config = $config;
        $this->multiTenantBootServiceMock = $multiTenantBootServiceMock;

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

    public function it_should_get_tenant_root_user_guid()
    {
        $tenant = new Tenant(
            id: 1,
            rootUserGuid: 1234567890123456,
        );

        $this->multiTenantBootServiceMock->getTenant()
            ->shouldBeCalled()
            ->willReturn($tenant);

        $this->getTenantOwnerGuid()
            ->shouldBe(1234567890123456);
    }
}
