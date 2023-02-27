<?php
namespace Minds\Core\Email\Mautic;

use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;

class Manager
{
    protected $create = [];
    protected $update = [];
    protected $offset = 0;

    public function __construct(
        protected ?MarketingAttributes\Manager $marketingAttributesManager = null,
        protected ?Client $client = null,
        protected ?Logger $logger = null
    ) {
        $this->marketingAttributesManager ??= Di::_()->get(MarketingAttributes\Manager::class);
        $this->client ??= Di::_()->get(Client::class);
        $this->logger ??= Di::_()->get('Logger');
    }

    /**
     * Syncs from our marketing attributes table to mautic
     * @param int $fromTs (optional) specify if you wish to sync from a certain time
     * @return void
     */
    public function sync(int $fromTs = null, int $offset = 0): void
    {
        $this->offset = $offset;
        foreach ($this->marketingAttributesManager->getList(fromTs: $fromTs, offset: $offset) as $row) {
            ++$this->offset;

            $row['minds_guid'] = $row['user_guid'];
            // Do we have the mautic_id?
            if (isset($row['mautic_id'])) {
                $row['id'] = $row['mautic_id'];
                $this->update[] = $row;
            } else {
                $this->create[] = $row;
            }
            $this->bulkContacts();
        }
        $this->bulkContacts(final: true);
    }

    /**
     * Bulk insert the contacts
     * @return void
     */
    private function bulkContacts(bool $final = false): void
    {
        if (count($this->update) >= 200 || $final) {
            $this->submit('PATCH', $this->update);
            $this->update = [];
        }
        if (count($this->create) >= 200 || $final) {
            $this->submit('PUT', $this->create);
            $this->create = [];
        }
    }

    /**
     * @param string $method
     * @param array $contacts
     */
    protected function submit(string $method, $contacts): void
    {
        $startTs = microtime(true);
    
        $response = $this->client->request($method, 'contacts/batch/edit', [
            'json' => $contacts
        ]);

        // $rawResponse = $response->getBody()->getContents();
        // $data = json_decode($rawResponse, true);
        //var_dump($data);

        $endTs = microtime(true);

        $latencySecs = $endTs - $startTs;
        $count = count($contacts);

        $verb = match ($method) {
            'PATCH' => 'Updated',
            'PUT' => 'Created'
        };

        $this->logger->info("$verb $count contacts in $latencySecs seconds. Offset: $this->offset");

        if (!in_array($response->getStatusCode(), [200, 201], true)) {
            $this->logger->error("FAILED with {$response->getStatusCode()}");
        }

        if ($method === 'PUT') {
            $rawResponse = $response->getBody()->getContents();
            $data = json_decode($rawResponse, true);
            foreach ($data['contacts'] as $k => $contact) {
                if ($data['statusCodes'][$k] === 201) {
                    $userGuid = $contact['fields']['all']['guid'];
                    $mauticId = $contact['id'];
                    $this->marketingAttributesManager->add($userGuid, 'mautic_id', $mauticId);
                    $this->logger->info("$userGuid synced to mautic id $mauticId");
                }
            }
        }
    }
}

