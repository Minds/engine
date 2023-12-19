<?php

namespace Spec\Minds\Core\Comments\EmbeddedComments\Services;

use Minds\Core\Comments\EmbeddedComments\Models\EmbeddedCommentsSettings;
use Minds\Core\Comments\EmbeddedComments\Repositories\EmbeddedCommentsSettingsRepository;
use Minds\Core\Comments\EmbeddedComments\Services\EmbeddedCommentsSettingsService;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Psr\SimpleCache\CacheInterface;

class EmbeddedCommentsSettingsServiceSpec extends ObjectBehavior
{
    private Collaborator $repositoryMock;
    private Collaborator $cacheMock;

    public function let(
        EmbeddedCommentsSettingsRepository $repositoryMock,
        CacheInterface $cacheMock,
    ) {
        $this->beConstructedWith($repositoryMock, $cacheMock);
        $this->repositoryMock = $repositoryMock;
        $this->cacheMock = $cacheMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(EmbeddedCommentsSettingsService::class);
    }

    public function it_should_return_settings()
    {
        // Cache will MISS
        $this->cacheMock->get('embedded-comments-plugin:settings:1')
            ->shouldBeCalled()
            ->willReturn(null);

        $settings = new EmbeddedCommentsSettings(
            userGuid: 1,
            domain: 'phpspec.local',
            pathRegex: '\/(.*)',
            autoImportsEnabled: true,
        );

        // Call from database
        $this->repositoryMock->getSettings(1)
            ->willReturn($settings);
            
        // Cache will be updated
        $this->cacheMock->set('embedded-comments-plugin:settings:1', serialize($settings))
            ->shouldBeCalled()
            ->willReturn(true);
            
        $settings = $this->getSettings(1);
        $settings->shouldBe($settings);
    }

    public function it_should_return_settings_from_cache()
    {
        $settings = new EmbeddedCommentsSettings(
            userGuid: 1,
            domain: 'phpspec.local',
            pathRegex: '\/(.*)',
            autoImportsEnabled: true,
        );
    
        // Cache will HIT
        $this->cacheMock->get('embedded-comments-plugin:settings:1')
            ->shouldBeCalled()
            ->willReturn(serialize($settings));

        // Call from database
        $this->repositoryMock->getSettings(1)
            ->shouldNotBeCalled();
            
        $settings = $this->getSettings(1);
        $settings->shouldBe($settings);
    }

    public function it_should_return_settings_bypassing_cache()
    {
        // Cache will MISS
        $this->cacheMock->get('embedded-comments-plugin:settings:1')
            ->shouldNotBeCalled();

        $settings = new EmbeddedCommentsSettings(
            userGuid: 1,
            domain: 'phpspec.local',
            pathRegex: '\/(.*)',
            autoImportsEnabled: true,
        );

        // Call from database
        $this->repositoryMock->getSettings(1)
            ->willReturn($settings);
            
        // Cache will be updated
        $this->cacheMock->set('embedded-comments-plugin:settings:1', serialize($settings))
            ->shouldBeCalled()
            ->willReturn(true);
            
        $settings = $this->getSettings(1, false);
        $settings->shouldBe($settings);
    }

    public function it_should_save_settings()
    {
        $settings = new EmbeddedCommentsSettings(
            userGuid: 1,
            domain: 'phpspec.local',
            pathRegex: '\/(.*)',
            autoImportsEnabled: true,
        );

        // Save to database
        $this->repositoryMock->setSettings($settings)
            ->willReturn(true);

        // Cache will be purged
        $this->cacheMock->delete('embedded-comments-plugin:settings:1')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->setSettings($settings)->shouldBe(true);
    }
}
