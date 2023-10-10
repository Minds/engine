<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Lago\Clients;

use GuzzleHttp\Client as HttpClient;

abstract class ApiClient
{
    public function __construct(
        protected readonly HttpClient $httpClient,
    ) {}
}
