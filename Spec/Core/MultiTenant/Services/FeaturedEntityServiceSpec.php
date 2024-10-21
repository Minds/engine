<?php

namespace Spec\Minds\Core\MultiTenant\Services;

use Minds\Core\Config\Config;
use Minds\Core\GraphQL\Types\PageInfo;
use Minds\Core\MultiTenant\Delegates\FeaturedEntityAddedDelegate;
use Minds\Core\MultiTenant\Enums\FeaturedEntityTypeEnum;
use Minds\Core\MultiTenant\Repositories\FeaturedEntitiesRepository;
use Minds\Core\MultiTenant\Services\FeaturedEntityService;
use Minds\Core\MultiTenant\Types\FeaturedEntity;
use Minds\Core\MultiTenant\Types\FeaturedEntityConnection;
use Minds\Core\MultiTenant\Types\FeaturedEntityEdge;
use Minds\Core\MultiTenant\Types\FeaturedGroup;
use Minds\Core\MultiTenant\Types\FeaturedUser;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class FeaturedEntityServiceSpec extends ObjectBehavior
{
    private Collaborator $repository;
    private Collaborator $featuredEntityAddedDelegate;
    private Collaborator $config;

    public function let(
        FeaturedEntitiesRepository $repository,
        FeaturedEntityAddedDelegate $featuredEntityAddedDelegate,
        Config $config,
    ) {
        $this->beConstructedWith($repository, $featuredEntityAddedDelegate, $config);
        $this->repository = $repository;
        $this->featuredEntityAddedDelegate = $featuredEntityAddedDelegate;
        $this->config = $config;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(FeaturedEntityService::class);
    }

    public function it_can_get_featured_entities()
    {
        $featuredUser1 = new FeaturedUser(
            tenantId: 123,
            entityGuid: '1234567891',
            autoSubscribe: true,
            recommended: true,
            username: 'username1',
            name: 'name1'
        );
        $featuredUser2 = new FeaturedUser(
            tenantId: 123,
            entityGuid: '1234567892',
            autoSubscribe: true,
            recommended: true,
            username: 'username2',
            name: 'name2'
        );

        $tenantId = 1;
        $type = FeaturedEntityTypeEnum::USER;
        $limit = 2;
        $loadAfter = 0;

        $this->repository->getFeaturedEntities(
            $tenantId,
            $type,
            $limit,
            $loadAfter,
            Argument::type('bool')
        )->willReturn([
            $featuredUser1,
            $featuredUser2
        ]);

        $result = $this->getFeaturedEntities(
            $type,
            $loadAfter,
            $limit,
            $tenantId
        );
        
        $result->shouldBeLike(
            (new FeaturedEntityConnection())
            ->setEdges([
                new FeaturedEntityEdge($featuredUser1, $loadAfter),
                new FeaturedEntityEdge($featuredUser2, $loadAfter)
            ])
            ->setPageInfo(new PageInfo(
                hasNextPage: false,
                hasPreviousPage: false, // not supported.
                startCursor: (string) $loadAfter,
                endCursor: (string) ($limit + $loadAfter),
            ))
        );
    }

    public function it_can_store_featured_entity(
        FeaturedEntity $featuredEntity,
        User $loggedInUser
    ) {
        $this->repository->upsertFeaturedEntity($featuredEntity)->willReturn($featuredEntity);
        
        $this->featuredEntityAddedDelegate->onAdd($featuredEntity, $loggedInUser)->shouldBeCalled();
        
        $this->storeFeaturedEntity($featuredEntity, $loggedInUser)->shouldBe($featuredEntity);
    }

    public function it_can_delete_featured_entity()
    {
        $entityGuid = 123456789;
        $tenantId = 123;

        $this->repository->deleteFeaturedEntity(
            tenantId: $tenantId,
            entityGuid: $entityGuid
        )->willReturn(true);

        $this->deleteFeaturedEntity($entityGuid, $tenantId)->shouldBe(true);
    }

    public function it_can_delete_featured_entity_with_no_tenant_id_passed()
    {
        $entityGuid = 123456789;
        $tenantId = 123;

        $this->config->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn($tenantId);

        $this->repository->deleteFeaturedEntity(
            tenantId: $tenantId,
            entityGuid: $entityGuid
        )->willReturn(true);

        $this->deleteFeaturedEntity($entityGuid)->shouldBe(true);
    }

    public function it_can_get_all_featured_entities_with_type(): void
    {
        $featuredUser1 = new FeaturedUser(
            tenantId: 123,
            entityGuid: '1234567891',
            autoSubscribe: true,
            recommended: true,
            username: 'username1',
            name: 'name1'
        );
        $featuredUser2 = new FeaturedUser(
            tenantId: 123,
            entityGuid: '1234567892',
            autoSubscribe: true,
            recommended: true,
            username: 'username2',
            name: 'name2'
        );

        $tenantId = 1;
        $type = FeaturedEntityTypeEnum::USER;

        $this->repository->getFeaturedEntities(
            $tenantId,
            $type,
            Argument::any(),
            Argument::any(),
            Argument::any(),
            false
        )->willReturn([
            $featuredUser1,
            $featuredUser2
        ]);

        $result = $this->getAllFeaturedEntities(
            $tenantId,
            $type
        );
        
        $result->shouldYield([
            $featuredUser1,
            $featuredUser2
        ]);
    }

    public function it_can_get_all_featured_entities_with_no_type(): void
    {
        $featuredUser1 = new FeaturedUser(
            tenantId: 123,
            entityGuid: '1234567891',
            autoSubscribe: true,
            recommended: true,
            username: 'username1',
            name: 'name1'
        );
        $featuredUser2 = new FeaturedUser(
            tenantId: 123,
            entityGuid: '1234567892',
            autoSubscribe: true,
            recommended: true,
            username: 'username2',
            name: 'name2'
        );
        $featuredGroup = new FeaturedGroup(
            tenantId: 123,
            entityGuid: '1234567893',
            autoSubscribe: true,
            recommended: true,
            name: 'name'
        );

        $tenantId = 1;
        $type = null;

        $this->repository->getFeaturedEntities(
            $tenantId,
            $type,
            Argument::any(),
            Argument::any(),
            Argument::any(),
            false
        )->willReturn([
            $featuredUser1,
            $featuredUser2,
            $featuredGroup
        ]);

        $result = $this->getAllFeaturedEntities(
            $tenantId,
            $type
        );
        
        $result->shouldYield([
            $featuredUser1,
            $featuredUser2,
            $featuredGroup
        ]);
    }
}
