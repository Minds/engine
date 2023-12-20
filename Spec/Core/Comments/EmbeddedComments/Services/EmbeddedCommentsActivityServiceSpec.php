<?php

namespace Spec\Minds\Core\Comments\EmbeddedComments\Services;

use Minds\Core\Comments\EmbeddedComments\Exceptions\InvalidScrapeException;
use Minds\Core\Comments\EmbeddedComments\Exceptions\InvalidUrlPatternException;
use Minds\Core\Comments\EmbeddedComments\Exceptions\OwnerDisabledAutoImportsException;
use Minds\Core\Comments\EmbeddedComments\Models\EmbeddedCommentsSettings;
use Minds\Core\Comments\EmbeddedComments\Repositories\EmbeddedCommentsRepository;
use Minds\Core\Comments\EmbeddedComments\Services\EmbeddedCommentsActivityService;
use Minds\Core\Comments\EmbeddedComments\Services\EmbeddedCommentsSettingsService;
use Minds\Core\Config\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Security\ACL;
use Minds\Core\Feeds\Activity\Manager as ActivityManager;
use Minds\Core\Feeds\Activity\RichEmbed\Metascraper\Service as MetascraperService;
use Minds\Core\Log\Logger;
use Minds\Entities\Activity;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class EmbeddedCommentsActivityServiceSpec extends ObjectBehavior
{
    private Collaborator $repositoryMock;
    private Collaborator  $embeddedCommentsSettingsServiceMock;
    private Collaborator  $configMock;
    private Collaborator  $aclMock;
    private Collaborator  $entitiesBuilderMock;
    private Collaborator  $metaScraperServiceMock;
    private Collaborator  $activityManagerMock;
    private Collaborator  $loggerMock;

    public function let(
        EmbeddedCommentsRepository $repositoryMock,
        EmbeddedCommentsSettingsService $embeddedCommentsSettingsServiceMock,
        Config $configMock,
        ACL $aclMock,
        EntitiesBuilder $entitiesBuilderMock,
        MetascraperService $metaScraperServiceMock,
        ActivityManager $activityManagerMock,
        Logger $loggerMock,
    ) {
        $this->beConstructedWith($repositoryMock, $embeddedCommentsSettingsServiceMock, $configMock, $aclMock, $entitiesBuilderMock, $metaScraperServiceMock, $activityManagerMock, $loggerMock);
    
        $this->repositoryMock = $repositoryMock;
        $this->embeddedCommentsSettingsServiceMock = $embeddedCommentsSettingsServiceMock;
        $this->configMock = $configMock;
        $this->aclMock = $aclMock;
        $this->entitiesBuilderMock = $entitiesBuilderMock;
        $this->metaScraperServiceMock = $metaScraperServiceMock;
        $this->activityManagerMock = $activityManagerMock;
        $this->loggerMock = $loggerMock;
    }
    

    public function it_is_initializable()
    {
        $this->shouldHaveType(EmbeddedCommentsActivityService::class);
    }

    public function it_should_return_existing_imported_activity()
    {
        $this->repositoryMock->getActivityGuidFromUrl('https://phpspec.local/canonical-url', 1)
            ->shouldBeCalled()
            ->willReturn(2);

        $this->entitiesBuilderMock->single(2)
            ->shouldBeCalled()
            ->willReturn(new Activity());

        $this
            ->withOwnerGuid(1)
            ->withUrl('https://phpspec.local/canonical-url')
            ->getActivityFromUrl()->shouldBeAnInstanceOf(Activity::class);
    }

    public function it_should_return_activity_from_canonical_url()
    {
        $this->repositoryMock->getActivityGuidFromUrl('https://phpspec.local/non-canonical-url', 1)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->repositoryMock->getActivityGuidFromUrl('https://phpspec.local/canonical-url', 1)
            ->shouldBeCalled()
            ->willReturn(2);

        $this->embeddedCommentsSettingsServiceMock->getSettings(1)
            ->shouldBeCalled()
            ->willReturn(new EmbeddedCommentsSettings(
                userGuid: 1,
                domain: 'phpspec.local',
                pathRegex: '\/(.*)',
                autoImportsEnabled: true,
            ));

        $this->entitiesBuilderMock->single(1)
            ->shouldBeCalled()
            ->willReturn(new User());

        $this->metaScraperServiceMock->scrape('https://phpspec.local/non-canonical-url')
            ->willReturn([
                'meta' => [
                    'canonical_url' => 'https://phpspec.local/canonical-url'
                ]
            ]);

        $this->entitiesBuilderMock->single(2)
            ->shouldBeCalled()
            ->willReturn(new Activity());

        $this
            ->withOwnerGuid(1)
            ->withUrl('https://phpspec.local/non-canonical-url')
            ->getActivityFromUrl()->shouldBeAnInstanceOf(Activity::class);
    }

    public function it_should_import_activity()
    {
        $this->repositoryMock->getActivityGuidFromUrl('https://phpspec.local/canonical-url', 1)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->embeddedCommentsSettingsServiceMock->getSettings(1)
            ->shouldBeCalled()
            ->willReturn(new EmbeddedCommentsSettings(
                userGuid: 1,
                domain: 'phpspec.local',
                pathRegex: '\/(.*)',
                autoImportsEnabled: true,
            ));

        $this->entitiesBuilderMock->single(1)
            ->shouldBeCalled()
            ->willReturn(new User());

        $this->metaScraperServiceMock->scrape('https://phpspec.local/canonical-url')
            ->willReturn([
                'meta' => [
                    'title' => 'A test post for phpspec',
                    'description' => 'A test description',
                    'canonical_url' => 'https://phpspec.local/canonical-url'
                ],
                'links' => [
                    'thumbnail' => [
                        [
                            'href' => 'https://phpspec.local/thumbnail.jpg'
                        ]
                    ]
                ]
            ]);

        $this->activityManagerMock->add(Argument::that(function (Activity $activity) {
            return true;
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->repositoryMock->addActivityGuidWithUrl(Argument::type('int'), 'https://phpspec.local/canonical-url', 1)
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->withOwnerGuid(1)
            ->withUrl('https://phpspec.local/canonical-url')
            ->getActivityFromUrl()->shouldBeAnInstanceOf(Activity::class);
    }

    public function it_should_not_import_if_disabled_imports()
    {
        $this->repositoryMock->getActivityGuidFromUrl('https://phpspec.local/canonical-url', 1)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->embeddedCommentsSettingsServiceMock->getSettings(1)
            ->shouldBeCalled()
            ->willReturn(new EmbeddedCommentsSettings(
                userGuid: 1,
                domain: 'phpspec.local',
                pathRegex: '\/(.*)',
                autoImportsEnabled: false,
            ));


        $this
            ->withOwnerGuid(1)
            ->withUrl('https://phpspec.local/canonical-url')
            ->shouldThrow(OwnerDisabledAutoImportsException::class)->duringGetActivityFromUrl();
    }

    public function it_should_not_import_if_invalid_domain()
    {
        $this->repositoryMock->getActivityGuidFromUrl('https://phpspec.local/canonical-url', 1)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->embeddedCommentsSettingsServiceMock->getSettings(1)
            ->shouldBeCalled()
            ->willReturn(new EmbeddedCommentsSettings(
                userGuid: 1,
                domain: 'isnotphpspec.local',
                pathRegex: '\/(.*)',
                autoImportsEnabled: true,
            ));

        $this
            ->withOwnerGuid(1)
            ->withUrl('https://phpspec.local/canonical-url')
            ->shouldThrow(InvalidUrlPatternException::class)->duringGetActivityFromUrl();
    }

    public function it_should_not_import_if_invalid_path()
    {
        $this->repositoryMock->getActivityGuidFromUrl('https://phpspec.local/canonical-url', 1)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->embeddedCommentsSettingsServiceMock->getSettings(1)
            ->shouldBeCalled()
            ->willReturn(new EmbeddedCommentsSettings(
                userGuid: 1,
                domain: 'phpspec.local',
                pathRegex: '\/blogs\/only-this-page',
                autoImportsEnabled: true,
            ));

        $this
            ->withOwnerGuid(1)
            ->withUrl('https://phpspec.local/canonical-url')
            ->shouldThrow(InvalidUrlPatternException::class)->duringGetActivityFromUrl();
    }

    public function it_should_not_import_if_invalid_scrape()
    {
        $this->repositoryMock->getActivityGuidFromUrl('https://phpspec.local/canonical-url', 1)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->embeddedCommentsSettingsServiceMock->getSettings(1)
            ->shouldBeCalled()
            ->willReturn(new EmbeddedCommentsSettings(
                userGuid: 1,
                domain: 'phpspec.local',
                pathRegex: '\/(.*)',
                autoImportsEnabled: true,
            ));

        $this->entitiesBuilderMock->single(1)
            ->shouldBeCalled()
            ->willReturn(new User());

        $this->metaScraperServiceMock->scrape('https://phpspec.local/canonical-url')
            ->willThrow(new ServerErrorException());

        $this
            ->withOwnerGuid(1)
            ->withUrl('https://phpspec.local/canonical-url')
            ->shouldThrow(InvalidScrapeException::class)->duringGetActivityFromUrl();
    }

}
