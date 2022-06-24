<?php
namespace Minds\Core\Email\SendGrid;

use Minds\Core\Di\Di;

class Manager
{
    /** @var SendGridListInterface[] */
    const DEFAULT_LISTS = [
        Lists\WireUSDUsersList::class,
        Lists\MonetizedUsersList::class,
        Lists\TwitterSyncList::class,
        Lists\YoutubeSyncList::class,
        Lists\EthUsersList::class,
        Lists\MembershipTierOwnerList::class,
        // Lists\Active30DayList::class,
    ];

    /** @var \SendGrid */
    protected $sendGrid;

    /** @var Logger */
    protected $logger;

    /** @var array */
    protected $fieldIds;

    /** @var array */
    protected $pendingContacts = [];

    public function __construct($sendGrid = null, $config = null, $logger = null)
    {
        $config = $config ?? Di::_()->get('Config');
        $this->sendGrid = $sendGrid ?? new \SendGrid($config->get('email')['sendgrid']['api_key']);
        $this->logger = $logger ??  Di::_()->get('Logger');
    }

    /**
     * Syncs our lists with SendGrid
     * @param SendGridListInterface[] $lists
     * @return void
     */
    public function syncContactLists(array $lists = []): void
    {
        if (empty($lists)) {
            $lists = array_map(function ($list) {
                return new $list;
            }, self::DEFAULT_LISTS);
        }
        $i = 0;
        foreach ($lists as $list) {
            foreach ($list->getContacts() as $contact) {
                ++$i;
                $export = $contact->export();
                $export['custom_fields'] = $this->patchCustomFields($export['custom_fields']);
                $this->pendingContacts[] =  $export;
                $this->logger->info("$i: (" . get_class($list) . ") {$export['first_name']}");

                if (count($this->pendingContacts) > 1000) {
                    $this->bulkContacts();
                }
            }
            $this->bulkContacts(); // any left over
        }
    }

    /**
     * Checks whether the service is accessible
     * @return bool
     */
    public function checkAvailability(): bool
    {
        $response = $this->sendGrid->client->marketing()->field_definitions()->get();
        return $response->statusCode() === 200;
    }

    /**
     * Bulk insert the contacts
     * @return void
     */
    private function bulkContacts(): void
    {
        $response = $this->sendGrid->client->marketing()->contacts()->put([ 'contacts' => $this->pendingContacts ]);
        if ($response->statusCode() !== 202) {
            $this->logger->error("FAILED with {$response->statusCode()}");
            var_dump($response);
            sleep(15);
            $this->bulkContacts();
            return;
        }
        $this->pendingContacts = [];
    }

    /**
     * Fetch the custom field ids from SendGrid
     * @return array
     */
    private function getCustomFieldIds(): array
    {
        $response = $this->sendGrid->client->marketing()->field_definitions()->get();
        $customFields = json_decode($response->body(), true)['custom_fields'];
        $fieldIds = [];
        foreach ($customFields as $field) {
            $fieldIds[$field['name']] = $field['id'];
        }
        return $fieldIds;
    }

    /**
     * Patch custom fields
     * @param array $customFields
     * @return array
     */
    private function patchCustomFields($customFields): array
    {
        if (!isset($this->fieldIds)) {
            $this->fieldIds = $this->getCustomFieldIds();
        }
        $patched = [];
        foreach ($customFields as $key => $value) {
            if (!isset($this->fieldIds[$key])) {
                continue;
            }
            $patched[$this->fieldIds[$key]] = $value;
        }
        return $patched;
    }
}
