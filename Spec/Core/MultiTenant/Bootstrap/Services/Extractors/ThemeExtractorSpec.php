<?php

namespace Spec\Minds\Core\MultiTenant\Bootstrap\Services\Extractors;

use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\ThemeExtractor;
use OpenAI\Contracts\ClientContract;
use OpenAI\Contracts\Resources\ChatContract;
use OpenAI\Responses\Chat\CreateResponse;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ThemeExtractorSpec extends ObjectBehavior
{
    private $openAiClientMock;
    private $chatMock;

    public function let(ClientContract $openAiClientMock, ChatContract $chatMock)
    {
        $this->openAiClientMock = $openAiClientMock;
        $this->chatMock = $chatMock;

        $openAiClientMock->chat()->willReturn($chatMock);

        $this->beConstructedWith($openAiClientMock);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ThemeExtractor::class);
    }

    public function it_should_extract_theme_from_screenshot()
    {
        $screenshotBlob = 'fake-screenshot-blob';
        $expectedTheme = [
            'color' => '#FF0000',
            'theme' => 'light'
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
                        'content' => json_encode($expectedTheme)
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

        $this->chatMock->create(Argument::that(function ($arg) use ($screenshotBlob) {
            return $arg['model'] === 'gpt-4o-mini' &&
                   $arg['temperature'] === 0.2 &&
                   $arg['messages'][1]['content'][1]['image_url']['url'] === 'data:image/jpeg;base64,' . base64_encode($screenshotBlob);
        }))->willReturn($response);

        $this->extract($screenshotBlob)->shouldReturn($expectedTheme);
    }
}
