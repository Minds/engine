<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\Services\Extractors;

use OpenAI\Contracts\ClientContract;

/**
 * Extracts content from a markdown file using OpenAI's API.
 */
class ContentExtractor
{
    /** @var int - The maximum number of articles to extract. */
    const MAX_ARTICLES = 10;

    public function __construct(private ClientContract $openAiClient)
    {
    }

    /**
     * Extracts content from a markdown file using OpenAI's API.
     * @param string $contentMarkdown - The markdown content to extract from.
     * @return array - The extracted content.
     */
    public function extract(string $contentMarkdown): array
    {
        $response = $this->openAiClient->chat()->create([
            'model' => 'gpt-4o-mini',
            'temperature' => 0,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an intelligent web content analyzer. You will be provided with a markdown representation of a website, and your task is to extract specific information and return it in JSON format.'
                ],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Please analyze the provided content and extract all posts, blogs, and articles. If an article has no image or description, return an empty string. If there are no posts, blogs, or articles, return an empty array.\nGenerate up to 2 hashtags for each article, based on the title and description. The hashtags should be in lowercase and without the # symbol. \nExtract max '.self::MAX_ARTICLES.' articles. Content: \n```'.$contentMarkdown.'```',
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
                            'articles' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'title' => [
                                            'type' => 'string'
                                        ],
                                        'description' => [
                                            'type' => 'string'
                                        ],
                                        'link' => [
                                            'type' => 'string'
                                        ],
                                        'image' => [
                                            'type' => 'string'
                                        ],
                                        'hashtags' => [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'string'
                                            ]
                                        ]
                                    ],
                                    'required' => ['title', 'description', 'image', 'link', 'hashtags'],
                                    'additionalProperties' => false
                                ]
                            ],
                        ],
                        'required' => ['articles'],
                        'additionalProperties' => false
                    ]
                ]
            ]
        ]);

        return json_decode($response->choices[0]->message->content, true);
    }
}
