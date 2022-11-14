<?php
declare(strict_types=1);

namespace Minds\Core\Verification\Helpers\OCR;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Minds\Core\Config\Config as MindsConfig;
use Minds\Core\Di\Di;

class GoogleVisionOCRClient implements MindsOCRInterface
{
    private const API_BASE_URI = 'https://vision.googleapis.com/v1/images:annotate';

    public function __construct(
        private ?MindsConfig $mindsConfig = null,
        private ?HttpClient $httpClient = null
    ) {
        $this->mindsConfig ??= Di::_()->get('Config');
        $this->httpClient ??= new HttpClient([
            'base_uri' => self::API_BASE_URI
        ]);
    }

    public function processImageScan(string $image): string|false
    {
        $response = $this->httpClient->postAsync("", [
            RequestOptions::HEADERS => [
                "Content-Type" => 'application/json; charset=utf-8'
            ],
            RequestOptions::QUERY => [
                'key' => $this->mindsConfig->get('ocr')['google']['api_key']
            ],
            RequestOptions::JSON => [
                'requests' => [
                    [
                        'image' => [
                            'content' => base64_encode($image)
                        ],
                        'features' => [
                            [
                                'type' => 'DOCUMENT_TEXT_DETECTION'
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        /**
         * @var Response $processedImage
         */
        $processedImage = $response->wait(true);

        $parsedResponse = json_decode($processedImage->getBody()->getContents());

        return $parsedResponse->responses[0]->fullTextAnnotation->text;
    }
}
