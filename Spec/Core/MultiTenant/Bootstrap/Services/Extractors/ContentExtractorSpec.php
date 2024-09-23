<?php

namespace Spec\Minds\Core\MultiTenant\Bootstrap\Services\Extractors;

use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\ContentExtractor;
use OpenAI\Contracts\ClientContract;
use OpenAI\Contracts\Resources\ChatContract;
use OpenAI\Responses\Chat\CreateResponse;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ContentExtractorSpec extends ObjectBehavior
{
    private $openAiClientMock;
    private $chatMock;

    public function let(
        ClientContract $openAiClientMock,
        ChatContract $chatMock
    ) {
        $openAiClientMock->chat()->willReturn($chatMock);

        $this->openAiClientMock = $openAiClientMock;
        $this->chatMock = $chatMock;

        $this->beConstructedWith($openAiClientMock);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ContentExtractor::class);
    }

    public function it_should_extract_content_from_markdown()
    {
        $contentMarkdown = "# Title\n\nThis is a sample markdown content.";
        $responseContent = [
            'articles' => [
                [
                    'title' => 'Title',
                    'description' => 'Description',
                    'link' => 'https://example.minds.com',
                    'image' => 'https://example.minds.com/image.jpg',
                    'hashtags' => ['sample', 'example']
                ]
            ]
        ];

        $response = CreateResponse::from([
            'id' => '1',
            'object' => 'chat.completion',
            'created' => time(),
            'model' => 'gpt-4o-mini',
            'choices' => [
                [
                    'index' => 0,
                    'message' => [
                        'role' => 'assistant',
                        'content' => json_encode($responseContent)
                    ],
                    'finish_reason' => 'stop'
                ]
            ],
            'usage' => [
                'prompt_tokens' => 100,
                'completion_tokens' => 200,
                'total_tokens' => 300
            ]
        ]);
            
        $this->chatMock->create(Argument::any())->willReturn($response);

        $this->extract($contentMarkdown)->shouldReturn($responseContent);
    }
}
