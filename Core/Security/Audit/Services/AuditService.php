<?php
namespace Minds\Core\Security\Audit\Services;

use DateTimeImmutable;
use Minds\Common\IpAddress;
use Minds\Core\Analytics\PostHog\PostHogQueryService;
use Minds\Core\Config\Config;
use Minds\Core\Log\Logger;
use Minds\Core\Security\Audit\Repositories\AuditRepository;
use Minds\Core\Sessions\ActiveSession;
use Minds\Entities\User;
use Zend\Diactoros\ServerRequestFactory;

class AuditService
{
    public function __construct(
        private AuditRepository $auditRepository,
        private ActiveSession $activeSession,
        private Logger $logger,
        private IpAddress $ipAddress,
        private PostHogQueryService $postHogQueryService,
        private Config $config,
    ) {
        
    }

    /**
     * Records an event fo audit logging
     */
    public function log(
        string $event,
        array $properties,
        ?User $user = null,
    ): void {
        if (!$user) {
            $user = $this->activeSession->getUser();
        }

        // Save the log to the database
        $this->auditRepository->log(
            event: $event,
            userGuid: $user?->getGuid(),
            properties: $properties,
            ipAddress: $this->ipAddress->get(),
            userAgent: $this->getServerRequestHeader('User-Agent') ? $this->getServerRequestHeader('User-Agent')[0]: '',
            referrer: $this->getServerRequestHeader('Referrer') ? $this->getServerRequestHeader('Referrer')[0] : '',
        );

        // Output to server logs
        $this->logger->info('[Audit]: ' . $event, [
            'user_guid' => $user?->getGuid(),
            ...$properties,
        ]);
    }

    /**
     * Returns audit logs
     */
    public function getLogs(
        int $limit,
        ?DateTimeImmutable $timestampGt,
        ?DateTimeImmutable $timestampLt,
        ?int &$nextEventId = null
    ): array {
        $rows = $this->auditRepository->list(
            limit: $limit,
            timestampGt: $timestampGt,
            timestampLt: $timestampLt,
            beforeId: $nextEventId,
        );

        if ($rows) {
            $nextEventId = (int) $rows[count($rows) - 1]['event_id'];
        }

        return $rows;
    }

    /**
     * Returns analytics capture events from posthog
     */
    public function getEvents(
        int $limit,
        ?DateTimeImmutable $timestampGt = null,
        ?DateTimeImmutable $timestampLt = null,
        ?string &$nextEventId = ''
    ): array {
        $tenantId = $this->config->get('tenant_id');
        $query = "SELECT uuid as event_id, event, person.properties.guid as user_guid, properties, created_at
            FROM events
            WHERE properties.tenant_id = $tenantId
            AND person.properties.guid IS NOT NULL";

        if ($nextEventId) {
            $query .= " AND uuid < '$nextEventId'";
        }

        if ($timestampGt) {
            $query .= " AND created_at > '" . $timestampGt->format('Y-m-d\TH:i:s') . "'";
        }

        if ($timestampLt) {
            $query .= " AND created_at < '" . $timestampLt->format('Y-m-d\TH:i:s') . "'";
        }

        $query .= " ORDER BY created_at DESC
            LIMIT $limit";

        $response = $this->postHogQueryService->query($query);

        $rows = array_map(function ($row) {
            return [
                'event_id' => $row[0],
                'event' => $row[1],
                'user_guid' => $row[2],
                'properties' => json_decode($row[3]),
                'created_at' => $row[4],
            ];
        }, $response['results']);

        if ($rows) {
            $nextEventId = (string) $rows[count($rows) - 1]['event_id'];
        }

        return $rows;
    }

    /**
     * @return string[]
     */
    private function getServerRequestHeader(string $header): array
    {
        $serverRequest = $this->serverRequest ?? ServerRequestFactory::fromGlobals();
        return $serverRequest->getHeader($header);
    }

    public function wrap($argument1)
    {
        // TODO: write logic here
    }
}
