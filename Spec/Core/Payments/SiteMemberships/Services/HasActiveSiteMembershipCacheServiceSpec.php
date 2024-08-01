<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Payments\SiteMemberships\Services;

use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Guid;
use Minds\Core\Payments\SiteMemberships\Services\HasActiveSiteMembershipCacheService;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class HasActiveSiteMembershipCacheServiceSpec extends ObjectBehavior
{
    private Collaborator $cacheMock;

    public function let(
        PsrWrapper $cacheMock
    ): void {
        $this->cacheMock = $cacheMock;
        $this->beConstructedWith($this->cacheMock);
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(HasActiveSiteMembershipCacheService::class);
    }

    // get

    public function it_should_get_true_value_from_cache()
    {
        $userGuid = Guid::build();
        
        $this->cacheMock->get("has_site_membership:$userGuid")
            ->shouldBeCalled()
            ->willReturn(1);

        $this->get($userGuid)->shouldReturn(true);
    }

    public function it_should_get_false_value_from_cache()
    {
        $userGuid = Guid::build();
        
        $this->cacheMock->get("has_site_membership:$userGuid")
            ->shouldBeCalled()
            ->willReturn(0);

        $this->get($userGuid)->shouldReturn(false);
    }

    public function it_should_get_no_value_from_cache()
    {
        $userGuid = Guid::build();
        
        $this->cacheMock->get("has_site_membership:$userGuid")
            ->shouldBeCalled()
            ->willReturn(false);

        $this->get($userGuid)->shouldReturn(null);
    }

    // set

    public function it_should_set_true_value_in_cache()
    {
        $userGuid = Guid::build();
        
        $this->cacheMock->set(
            key: "has_site_membership:$userGuid",
            value: 1,
            ttl: 3600
        )->shouldBeCalled();

        $this->set($userGuid, true, 3600);
    }

    public function it_should_set_false_value_in_cache()
    {
        $userGuid = Guid::build();
        
        $this->cacheMock->set(
            key: "has_site_membership:$userGuid",
            value: 0,
            ttl: 3600
        )->shouldBeCalled();

        $this->set($userGuid, false, 3600);
    }

    // delete

    public function it_should_delete_value_from_cache()
    {
        $userGuid = Guid::build();
        
        $this->cacheMock->delete("has_site_membership:$userGuid")
            ->shouldBeCalled();

        $this->delete($userGuid);
    }
}
