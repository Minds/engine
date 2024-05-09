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
use OpenApi\Attributes as OA;

class SiteMembershipBatchPsrController
{
    public function __construct(
        private SiteMembershipBatchService $batchService,
    ) {
        
    }

    /**
     * Allows for an admin, with a Personal Api Key, to issue site membership subscriptions
     * to their users
     */
    #[OA\Post(
        path: '/api/v3/payments/site-memberships/batch',
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(
                                property: 'id_type',
                                enum: SiteMembershipBatchIdTypeEnum::class,
                            ),
                            new OA\Property(
                                property: 'id',
                                type: "string",
                                description: "The ID (that relates to the id_type). If passing an OIDC id, use the format '{providerId}::{sub}'"
                            ),
                            new OA\Property(
                                property: 'membership_guid',
                                type: ['string', 'number']
                            ),
                            new OA\Property(
                                property: 'valid_from',
                                type: 'string'
                            ),
                            new OA\Property(
                                property: 'valid_to',
                                type: 'string',
                            )
                    ]
                    ),
                    examples: [
                        new OA\Examples(
                            example: 'A batch request with multiple different id types provided',
                            summary: '',
                            value: [
                                [
                                    'id_type' => 'EMAIL',
                                    'id' => 'test@minds.com',
                                    'membership_guid' => 1604887628371464195,
                                    'valid_from' => '2024-05-01',
                                    'valid_to' => '2024-06-01',
                                ],
                                [
                                    'id_type' => 'GUID',
                                    'id' => 1404887628371464196,
                                    'membership_guid' => 1604887628371464195,
                                    'valid_from' => '2024-05-01',
                                    'valid_to' => '2024-06-01',
                                ],
                                [
                                    'id_type' => 'OIDC',
                                    'id' => '1::241849093897463702',
                                    'membership_guid' => 1604887628371464195,
                                    'valid_from' => '2024-05-01',
                                    'valid_to' => '2025-05-01',
                                ]
                            ]
                        )
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Ok')]
    #[OA\Response(response: 400, description: 'Bad request')]
    #[OA\Response(response: 403, description: 'Forbidden')]
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
