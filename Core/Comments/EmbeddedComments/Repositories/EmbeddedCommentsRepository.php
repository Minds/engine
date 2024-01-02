<?php
namespace Minds\Core\Comments\EmbeddedComments\Repositories;

use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\AbstractRepository;
use PDO;
use Selective\Database\Operator;
use Selective\Database\RawExp;

class EmbeddedCommentsRepository extends AbstractRepository
{
    const TABLE_NAME = 'minds_embedded_comments_activity_map';

    /**
     * Returns an activity guid from a url.
     */
    public function getActivityGuidFromUrl(string $url, int $userGuid): ?int
    {
        $query = $this->mysqlClientReaderHandler->select()
            ->from(static::TABLE_NAME)
            ->columns([
                'activity_guid'
            ])
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('user_guid', Operator::EQ, new RawExp(':user_guid'))
            ->where('url', Operator::EQ, new RawExp(':url'));
    
        $stmt = $query->prepare();

        $stmt->execute([
            'tenant_id' => $this->getTenantId(),
            'user_guid' => $userGuid,
            'url' => $url,
        ]);

        if ($stmt->rowCount() === 0) {
            return null;
        }

        return (int) $stmt->fetchAll(PDO::FETCH_ASSOC)[0]['activity_guid'];
    }

    /**
     * Pairs an activity guid with a url
     */
    public function addActivityGuidWithUrl(int $guid, string $url, int $userGuid): bool
    {
        $query = $this->mysqlClientWriterHandler->insert()
            ->into(static::TABLE_NAME)
            ->set([
                'tenant_id' => new RawExp(':tenant_id'),
                'user_guid' => new RawExp(':user_guid'),
                'activity_guid' => new RawExp(':activity_guid'),
                'url' => new RawExp(':url')
            ]);

        $stmt = $query->prepare();

        return $stmt->execute([
            'tenant_id' => $this->getTenantId(),
            'user_guid' => $userGuid,
            'activity_guid' => $guid,
            'url' => $url,
        ]);
    }

    private function getTenantId(): int
    {
        return $this->config->get('tenant_id') ?? -1;
    }
}
