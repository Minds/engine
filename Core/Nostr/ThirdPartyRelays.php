<?php
namespace Minds\Core\Nostr;

class ThirdPartyRelays
{
    /** @var \WebSocket\Client[] */
    protected array $clients = [];

    public function __construct(array $clients = [])
    {
        $this->clients = $clients;
    }

    /**
     * Unsubscribes from clients
     */
    public function __destruct()
    {
        if ($this->clients) {
            foreach ($this->clients as $client) {
                try {
                    $client->close();
                } catch (\WebSocket\ConnectionException $e) {
                }
            }
        }
    }

    /**
     * Emit event to nostr
     * @param NostrEvent $nostrEvent
     * @return void
     */
    public function emitEvent(NostrEvent $nostrEvent): void
    {
        $jsonPayload = json_encode(
            [
                "EVENT",
                $nostrEvent->export(),
            ],
            JSON_UNESCAPED_SLASHES
        );

        // TODO: Should manager do this or should this class
        // if (!$this->verifyEvent($jsonPayload)) {
        //     throw new ServerErrorException("Error in signing event");
        // }

        foreach ($this->getClients() as $client) {
            try {
                $client->text($jsonPayload);
                //echo $client->receive(); // Do we care?
                //$client->close();
            } catch (\WebSocket\ConnectionException $e) {
                //var_dump($jsonPayload);
            }
        }
    }

    /**
     * Returns the clients, constructs them if empty
     * @return \WebSocket\Client[]
     */
    protected function getClients(): array
    {
        if ($this->clients) {
            return $this->clients;
        }
        $relays = $this->config->get('nostr')['relays'] ?? [
                'wss://nostr-relay.untethr.me',
                'wss://nostr.bitcoiner.social',
                'wss://nostr-relay.wlvs.space',
                'wss://nostr-pub.wellorder.net'
            ];

        $this->clients = [];

        foreach ($relays as $relay) {
            $this->clients[] = new \WebSocket\Client($relay, [
                'headers' => [
                    'Host' => ltrim($relay, 'wss://'),
                ]
            ]);
        }
        return $this->clients;
    }
}
