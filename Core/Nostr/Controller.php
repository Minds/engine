<?php

namespace Minds\Core\Nostr;

use Minds\Api\Exportable;
use Minds\Core\Di\Di;
use Minds\Core\Nostr\RequestValidators\GetEventsRequestValidator;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

class Controller
{
    public function __construct(
        protected ?Manager $manager = null,
        protected ?PocSync $pocSync = null,
        protected ?EntityImporter $entityImporter = null,
        protected ?EntityExporter $entityExporter = null,
    ) {
        $this->manager ??= Di::_()->get('Nostr\Manager');
        $this->pocSync ??= Di::_()->get('Nostr\PocSync');
        $this->entityImporter ??= new EntityImporter();
        $this->entityExporter ??= new EntityExporter();
    }

    /**
     * https://github.com/nostr-protocol/nips/blob/master/05.md
     * ie. /.well-known/nostr.json?name=mark
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws NotFoundException
     */
    public function resolveNip05(ServerRequest $request): JsonResponse
    {
        $name = $request->getQueryParams()['name'] ?? null;

        if (!$name) {
            throw new UserErrorException("?name must be provided");
        }

        $publicKey = $this->manager->getPublicKeyFromUsername($name);

        return new JsonResponse(
            [
                "names" => [
                    $name => $publicKey,
                ]
            ],
            200,
            [
                'Access-Control-Allow-Origin' => '*',
            ]
        );
    }

    /**
     * /api/v3/nostr/sync
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws NotFoundException
     */
    public function sync(ServerRequest $request): JsonResponse
    {
        $username = $request->getQueryParams()['username'] ?? null;

        if (!$username) {
            throw new UserErrorException("?name must be provided");
        }

        $this->pocSync->syncChannel($username);

        return new JsonResponse([]);
    }

    /**
    * GET /api/v3/nostr/req
    *
    * This endpoint will support support REQ
    *
    * @param ServerRequestInterface $request
    * @return JsonResponse
    * @throws NotFoundException
    * @throws UserErrorException
    * @throws ServerErrorException
    */
    public function getReq(ServerRequestInterface $request): JsonResponse
    {

        // ?filters = base64_encode(
        //   {
        //     "ids": <a list of event ids or prefixes>,
        //     "authors": <a list of pubkeys or prefixes, the pubkey of an event must be one of these>,
        //     "kinds": <a list of a kind numbers>,
        //     "#e": <a list of event ids that are referenced in an "e" tag>,
        //     "#p": <a list of pubkeys that are referenced in a "p" tag>,
        //     "since": <a timestamp, events must be newer than this to pass>,
        //     "until": <a timestamp, events must be older than this to pass>,
        //     "limit": <maximum number of events to be returned in the initial query>
        //   }
        // )

        $filtersRaw = $request->getQueryParams()['filters'];

        $filters = json_decode($filtersRaw, true);

        $nostrEvents = [...$this->entityExporter->getNostrReq($filters)];

        return new JsonResponse(Exportable::_($nostrEvents));
    }

    /**
    * PUT /api/v3/nostr/event
    *
    * This endpoint will create nostr posts on Minds
    *
    * @param ServerRequestInterface $request
    * @return JsonResponse
    * @throws NotFoundException
    * @throws UserErrorException
    * @throws ServerErrorException
    */
    public function putEvent(ServerRequestInterface $request): JsonResponse
    {

        // Request body should be

        // {
        //     "id": <32-bytes sha256 of the the serialized event data>
        //     "pubkey": <32-bytes hex-encoded public key of the event creator>,
        //     "created_at": <unix timestamp in seconds>,
        //     "kind": <integer>,
        //     "tags": [
        //         ["e", <32-bytes hex of the id of another event>, <recommended relay URL>],
        //         ["p", <32-bytes hex of the key>, <recommended relay URL>],
        //         ... // other kinds of tags may be included later
        //     ],
        //     "content": <arbitrary string>,
        //     "sig": <64-bytes signature of the sha256 hash of the serialized event data, which is the same as the "id" field>
        // }

        $rawEvent = $request->getParsedBody();

        $nostrEvent = NostrEvent::buildFromArray($rawEvent);

        $this->entityImporter->onNostrEvent($nostrEvent);

        return new JsonResponse([]);
    }
}
