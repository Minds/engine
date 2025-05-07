<?php
namespace Minds\Core\Security\Audit;

use DateInterval;
use DateTimeImmutable;
use Minds\Core\Security\Audit\Services\AuditService;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class Controller
{
    public function __construct(
        private AuditService $auditService,
    ) {
        
    }
    
    /**
     * Returns the audit logs
     * Example queries:
     *  /api/v3/security/audit/logs?limit=3&next=8
     *  /api/v3/security/audit/logs?timestamp_gt=2025-05-07%2009:49:27
     */
    public function getLogs(ServerRequestInterface $request): JsonResponse
    {
        $queryParams = $request->getQueryParams();

        $limit = min($queryParams['limit'] ?? 1000, 1000);

        $timestampLt = new DateTimeImmutable($queryParams['timestamp_lt'] ?? 'now');
        $timestampGt = isset($queryParams['timestamp_gt']) ? new DateTimeImmutable($queryParams['timestamp_gt']) : $timestampLt->sub(new DateInterval('P1Y'));

        $nextEventId = $queryParams['next'] ?? 0;

        $logs = $this->auditService->getLogs(
            limit: $limit,
            timestampGt: $timestampGt,
            timestampLt: $timestampLt,
            nextEventId: $nextEventId,
        );

        return new JsonResponse([
            'logs' => $logs,
            'next' => $nextEventId,
        ]);
    }

    /**
     * Returns the event logs
     * Example queries:
     *  /api/v3/security/audit/logs?limit=3&next=8
     *  /api/v3/security/audit/logs?timestamp_gt=2025-05-07%2009:49:27
     */
    public function getEvents(ServerRequestInterface $request): JsonResponse
    {
        $queryParams = $request->getQueryParams();

        $limit = min($queryParams['limit'] ?? 1000, 1000);

        $timestampLt = new DateTimeImmutable($queryParams['timestamp_lt'] ?? 'now');
        $timestampGt = isset($queryParams['timestamp_gt']) ? new DateTimeImmutable($queryParams['timestamp_gt']) : $timestampLt->sub(new DateInterval('P1Y'));

        $nextEventId = $queryParams['next'] ?? 0;

        $logs = $this->auditService->getEvents(
            limit: $limit,
            timestampGt: $timestampGt,
            timestampLt: $timestampLt,
            nextEventId: $nextEventId,
        );

        return new JsonResponse([
            'logs' => $logs,
            'next' => $nextEventId,
        ]);
    }
}
