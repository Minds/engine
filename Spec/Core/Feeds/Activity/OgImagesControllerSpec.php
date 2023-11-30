<?php

namespace Spec\Minds\Core\Feeds\Activity;

use ElggFile;
use Minds\Core\Feeds\Activity\OgImagesController;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Media\Imagick;
use Minds\Core\Media\Thumbnails;
use Minds\Core\Security\ACL;
use Minds\Entities\Activity;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Zend\Diactoros\ServerRequest;

class OgImagesControllerSpec extends ObjectBehavior
{
    protected $entitiesBuilderMock;
    protected $aclMock;
    protected $imagickManager;
    protected $mediaThumbnailsMock;

    public function let(
        EntitiesBuilder $entitiesBuilderMock,
        ACL $aclMock,
        Imagick\Manager $imagickManager,
        Thumbnails $mediaThumbnailsMock,
    ) {
        $this->beConstructedWith($entitiesBuilderMock, $aclMock, $imagickManager, $mediaThumbnailsMock);

        $this->entitiesBuilderMock = $entitiesBuilderMock;
        $this->aclMock = $aclMock;
        $this->imagickManager = $imagickManager;
        $this->mediaThumbnailsMock = $mediaThumbnailsMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(OgImagesController::class);
    }

    public function it_should_generate_a_supermind_image(ServerRequest $requestMock, Activity $activityMock, User $ownerMock)
    {
        $requestMock->getAttribute('parameters')
            ->willReturn([
                'guid' => '123'
            ]);
        
        $this->entitiesBuilderMock->single('123')
            ->willReturn($activityMock);

        $this->aclMock->read($activityMock)
            ->willReturn(true);

        $activityMock->getSupermind()
            ->willReturn([
                'is_supermind' => true,
            ]);
        $activityMock->getMessage()
            ->willReturn('hello world');
        $activityMock->getOwnerGuid()
            ->willReturn(456);

        $this->entitiesBuilderMock->single(456)
            ->willReturn($ownerMock);

        $ownerMock->getUsername()
            ->willReturn('phpspec');

        $this->imagickManager->annotate(
            width: Argument::any(),
            text: 'hello world',
            username: 'phpspec'
        )
            ->shouldBeCalled()
            ->willReturn(new \Imagick());

        $this->renderOgImage($requestMock);
    }

    public function it_should_deliver_an_image(ServerRequest $requestMock, Activity $activityMock, User $ownerMock)
    {
        $requestMock->getAttribute('parameters')
            ->willReturn([
                'guid' => '123'
            ]);
        
        $this->entitiesBuilderMock->single('123')
            ->willReturn($activityMock);

        $this->aclMock->read($activityMock)
            ->willReturn(true);

        $activityMock->getOwnerGuid()
            ->willReturn(456);

        $this->entitiesBuilderMock->single(456)
            ->willReturn($ownerMock);

        $activityMock->getSupermind()
            ->willReturn(null);
        $activityMock->getMessage()
            ->willReturn('hello world');
        $activityMock->hasAttachments()
            ->willReturn(true);
        $activityMock->getCustomType()
            ->willReturn('batch');
        $activityMock->getCustomData()
            ->willReturn([
                [
                    'guid' => '456',
                ]
            ]);

        $this->mediaThumbnailsMock->get('456', 'xlarge')
            ->willReturn(new ElggFile());
        

        $this->renderOgImage($requestMock);
    }

    public function it_should_show_default_logo(ServerRequest $requestMock)
    {
        $requestMock->getAttribute('parameters')
            ->willReturn([
                'guid' => '123'
            ]);
        
        $this->entitiesBuilderMock->single('123')
            ->willReturn(null);

        $this->renderOgImage($requestMock);
    }
}
