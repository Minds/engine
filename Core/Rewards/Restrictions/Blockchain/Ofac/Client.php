<?php

namespace Minds\Core\Rewards\Restrictions\Blockchain\Ofac;

use \GuzzleHttp\Client as GuzzleClient;
use SimpleXMLElement;

/**
 * Client for getting and parsing relevant data on OFAC sanctions.
 */
class Client
{
    public function __construct(
        private ?GuzzleClient $guzzleClient = null,
    ) {
        $this->guzzleClient ??= new GuzzleClient();
    }

    /**
     * Get all by parsing response.
     * @return array array with nested ['network', 'address'] for all relevant sanctions.
     */
    public function getAll(): array
    {
        $xmlDoc = $this->request();
        $addresses = [];

        foreach ($xmlDoc->sdnEntry as $item) {
            if (empty($item->idList)) {
                continue;
            }
            
            if (
                $item->idList->id->count() === 1
            ) {
                if (str_starts_with((string)$item->idList->id->idType, "Digital Currency Address")) {
                    $addresses[] = $item->idList->id;
                }
            } else {
                $addresses = array_merge(
                    $addresses,
                    array_filter(((array) $item->idList)['id'], function (SimpleXMLElement $value, string $key): bool {
                        return str_starts_with((string)$value->idType, "Digital Currency Address");
                    }, ARRAY_FILTER_USE_BOTH)
                );
            }
        }

        $data = [];

        foreach ($addresses as $address) {
            $data[] = [
                'network' => explode(' - ', (string) $address->idType)[1],
                'address' => (string) $address->idNumber
            ];
        }

        return $data;
    }

    /**
     * Request XML from OFAC list.
     * @return SimpleXMLElement
     */
    private function request(): SimpleXMLElement
    {
        $response = $this->guzzleClient->request('GET', 'https://www.treasury.gov/ofac/downloads/sdn.xml', [
            'headers' => [
                'Accept' => 'application/xml',
            ]
        ]);

        return new SimpleXMLElement($response->getBody()->getContents());
    }
}
