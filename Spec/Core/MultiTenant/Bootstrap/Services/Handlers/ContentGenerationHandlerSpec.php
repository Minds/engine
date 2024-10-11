<?php

namespace Spec\Minds\Core\MultiTenant\Bootstrap\Services\Handlers;

use Minds\Core\MultiTenant\Bootstrap\Services\Handlers\ContentGenerationHandler;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\ContentExtractor;
use Minds\Core\MultiTenant\Bootstrap\Delegates\ActivityCreationDelegate;
use Minds\Core\MultiTenant\Bootstrap\Repositories\BootstrapProgressRepository;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Bootstrap\Delegates\ContentGeneratedSocketDelegate;
use Minds\Core\MultiTenant\Bootstrap\Enums\BootstrapStepEnum;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ContentGenerationHandlerSpec extends ObjectBehavior
{
    private $contentExtractorMock;
    private $activityCreationDelegateMock;
    private $progressRepositoryMock;
    private $contentGeneratedSocketDelegateMock;
    private $loggerMock;

    public function let(
        ContentExtractor $contentExtractor,
        ActivityCreationDelegate $activityCreationDelegate,
        BootstrapProgressRepository $progressRepository,
        ContentGeneratedSocketDelegate $contentGeneratedSocketDelegate,
        Logger $logger
    ) {
        $this->contentExtractorMock = $contentExtractor;
        $this->activityCreationDelegateMock = $activityCreationDelegate;
        $this->progressRepositoryMock = $progressRepository;
        $this->contentGeneratedSocketDelegateMock = $contentGeneratedSocketDelegate;
        $this->loggerMock = $logger;

        $this->beConstructedWith($contentExtractor, $activityCreationDelegate, $progressRepository, $contentGeneratedSocketDelegate, $logger);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ContentGenerationHandler::class);
    }

    public function it_should_handle_content_generation_with_markdown(User $user)
    {
        $contentMarkdown = '# Title \n\ Markdown content.';
        $articles = [
            [
                'title' => 'Title',
                'description' => 'Description',
                'link' => 'https://example.minds.com',
                'image' => 'https://example.minds.com/image.jpg',
                'hashtags' => ['sample', 'example']
            ]
        ];

        $this->contentExtractorMock->extract($contentMarkdown)->willReturn(['articles' => $articles]);

        $this->activityCreationDelegateMock->onBulkCreate($articles, $user)->shouldBeCalled();

        $this->progressRepositoryMock->updateProgress(BootstrapStepEnum::CONTENT_STEP, true)->shouldBeCalled();

        $this->contentGeneratedSocketDelegateMock->onContentGenerated()
            ->shouldBeCalled();

        $this->handle($contentMarkdown, $user);
    }

    public function it_should_handle_content_generation_without_markdown(User $user)
    {
        $this->activityCreationDelegateMock->onBulkCreate(Argument::any(), $user)
            ->shouldNotBeCalled();

        $this->progressRepositoryMock->updateProgress(BootstrapStepEnum::CONTENT_STEP, false)->shouldBeCalled();

        $this->handle(null, $user);
    }

    public function it_should_handle_errors_during_content_generation(User $user)
    {
        $contentMarkdown = '# Title \n Markdown content.';

        $this->contentExtractorMock->extract($contentMarkdown)->willThrow(new \Exception('Content extraction failed'));

        $this->progressRepositoryMock->updateProgress(BootstrapStepEnum::CONTENT_STEP, false)->shouldBeCalled();

        $this->handle($contentMarkdown, $user);
    }
}
