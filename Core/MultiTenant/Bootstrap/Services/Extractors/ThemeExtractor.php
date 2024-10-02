<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\Services\Extractors;

use OpenAI\Contracts\ClientContract;

/**
 * Extracts the theme of a website using OpenAI.
 */
class ThemeExtractor
{
    public function __construct(
        private ClientContract $openAiClient
    ) {
    }

    /**
     * Extracts the theme of a website using OpenAI from a given screenshot.
     * @param string $screenshotBlob - The screenshot to generate a theme from.
     * @return array - The theme of the website.
     */
    public function extract(string $screenshotBlob): ?array
    {
        $response = $this->openAiClient->chat()->create([
            'model' => 'gpt-4o-mini',
            'temperature' => 0.2,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a theme analyzer for websites. You will be given a website screenshot and you will return the requested information in JSON format'
                ],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Analyze the website screenshot and identify the main color and the theme (light or dark). The main color is found in action buttons, links, the main logo, or other highlighted elements. The main color is NOT the text or background color (ignore empty boxes and site images). Return the main color as a web hex color and the theme as "light" or "dark"',
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => 'data:image/jpeg;base64,' . base64_encode($screenshotBlob)
                            ]
                        ],
                    ],
                ],
            ],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'json_response',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'color' => [
                                'type' => 'string',
                            ],
                            'theme' => [
                                'type' => 'string',
                                'enum' => ['light', 'dark']
                            ]
                        ],
                        'required' => ['color', 'theme'],
                        'additionalProperties' => false
                    ]
                ]
            ]
        ]);

        return json_decode($response->choices[0]->message->content, true);
    }
}
