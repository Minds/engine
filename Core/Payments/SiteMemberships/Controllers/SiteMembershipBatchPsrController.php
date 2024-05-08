<?php
namespace Minds\Core\Payments\SiteMemberships\Controllers;

use DateTimeImmutable;
use Minds\Api\Exportable;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipBatchIdTypeEnum;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipBatchService;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembershipBatchUpdate;
use Minds\Exceptions\UserErrorException;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class SiteMembershipBatchPsrController
{
    public function __construct(
        private SiteMembershipBatchService $batchService,
    ) {
        
    }
    public function onBatchRequest(ServerRequestInterface $request): JsonResponse
    {
        $rows = $request->getParsedBody();

        if (!is_array($rows)) {
            throw new UserErrorException("You must provide an array of items");
        }

        if (count($rows) > 500) {
            throw new UserErrorException("You can only send up to 500 items in a request");
        }

        $items = [];

        foreach ($rows as $row) {
            try {
                $idType = constant(SiteMembershipBatchIdTypeEnum::class . '::'  . $row['id_type']);
            } catch (\Error) {
                throw new UserErrorException("Invalid id_type {$row['id_type']} was provided");
            }
            $items[] = new SiteMembershipBatchUpdate(
                idType: $idType,
                id: $row['id'],
                membershipGuid: $row['membership_guid'],
                validFrom: new DateTimeImmutable($row['valid_from']),
                validTo: new DateTimeImmutable($row['valid_to']),
            );
        }

        $this->batchService->process($items);

        return new JsonResponse([
            'items' => Exportable::_($items),
        ]);
    }
}
