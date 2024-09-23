<?php

namespace Spec\Minds\Core\MultiTenant\Bootstrap\Delegates;

use Minds\Core\MultiTenant\Bootstrap\Delegates\ActivityCreationDelegate;
use Minds\Core\Feeds\Activity\Manager as ActivityManager;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\MetadataExtractor;
use Minds\Core\Security\ACL;
use Minds\Entities\Activity;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class ActivityCreationDelegateSpec extends ObjectBehavior
{
    private Collaborator $activityManagerMock;
    private Collaborator $metadataExtractorMock;
    private Collaborator $aclMock;
    private Collaborator $loggerMock;

    public function let(ActivityManager $activityManagerMock, MetadataExtractor $metadataExtractorMock, ACL $aclMock, Logger $loggerMock)
    {
        $this->activityManagerMock = $activityManagerMock;
        $this->metadataExtractorMock = $metadataExtractorMock;
        $this->aclMock = $aclMock;
        $this->loggerMock = $loggerMock;

        $this->beConstructedWith($activityManagerMock, $metadataExtractorMock, $aclMock, $loggerMock);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ActivityCreationDelegate::class);
    }

    public function it_should_create_activities_from_articles(User $user)
    {
        $userGuid = 1234567890;
        $exportedUser = ['guid' => $userGuid];
        $user->getGuid()->willReturn($userGuid);
        $user->export()->willReturn($exportedUser);

        $articles = [
            [
                'link' => 'https://example.minds.com/article1',
                'description' => 'Description 1',
                'title' => 'Title 1',
                'hashtags' => ['tag1', 'tag2']
            ],
            [
                'link' => 'https://example.minds.com/article2',
                'description' => 'Description 2',
                'title' => 'Title 2',
                'hashtags' => ['tag3', 'tag4']
            ]
        ];

        $this->metadataExtractorMock->extractThumbnailUrl('https://example.minds.com/article1')->willReturn('https://example.minds.com/image1.jpg');
        $this->metadataExtractorMock->extractThumbnailUrl('https://example.minds.com/article2')->willReturn('https://example.minds.com/image1.jpg');

        foreach ($articles as $item) {
            $this->activityManagerMock->add(Argument::that(function (Activity $activity) use ($item, $userGuid, $exportedUser) {
                return $activity->getMessage() === $item['description'] &&
                    $activity->getTags() === $item['hashtags'] &&
                    $activity->getLinkTitle() === $item['title'] &&
                    $activity->getPermaURL() === $item['link'] &&
                    $activity->getBlurb() === $item['description'] &&
                    $activity->getThumbnail() === 'https://example.minds.com/image1.jpg' &&
                    $activity->getContainerGUID() === $userGuid;
            }))->shouldBeCalled();
        }

        $this->onBulkCreate($articles, $user);
    }
}
