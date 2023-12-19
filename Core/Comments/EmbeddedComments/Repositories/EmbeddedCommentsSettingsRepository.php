<?php
namespace Minds\Core\Comments\EmbeddedComments\Repositories;

use Minds\Core\Comments\EmbeddedComments\Models\EmbeddedCommentsSettings;
use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\AbstractRepository;
use PDO;
use Selective\Database\Operator;
use Selective\Database\RawExp;

class EmbeddedCommentsSettingsRepository extends AbstractRepository
{
    const TABLE_NAME = 'minds_embedded_comments_settings';

    public function __construct(
        private Config $config,
        ...$args,
    ) {
        parent::__construct(...$args);
    }

    /**
     * Returns settings for a user
     */
    public function getSettings(int $userGuid): ?EmbeddedCommentsSettings
    {
        $query = $this->mysqlClientReaderHandler->select()
            ->from(static::TABLE_NAME)
            ->columns([
                'user_guid',
                'domain',
                'path_regex',
                'auto_imports_enabled',
            ])
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('user_guid', Operator::EQ, new RawExp(':user_guid'));
    
        $stmt = $query->prepare();

        $stmt->execute([
            'tenant_id' => $this->getTenantId(),
            'user_guid' => $userGuid,
        ]);

        if ($stmt->rowCount() === 0) {
            return null;
        }

        $row = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];

        return new EmbeddedCommentsSettings(
            userGuid: (int) $row['user_guid'],
            domain: $row['domain'],
            pathRegex: $row['path_regex'],
            autoImportsEnabled: (bool) $row['auto_imports_enabled'],
        );
    }

    /**
     * Saves the settings
     */
    public function setSettings(EmbeddedCommentsSettings $settings): bool
    {
        $query = $this->mysqlClientWriterHandler->insert()
            ->into(static::TABLE_NAME)
            ->set([
                'tenant_id' => new RawExp(':tenant_id'),
                'user_guid' => new RawExp(':user_guid'),
                'domain' => new RawExp(':domain'),
                'path_regex' => new RawExp(':path_regex'),
                'auto_imports_enabled' => new RawExp(':auto_imports_enabled')
            ])
            ->onDuplicateKeyUpdate([
                'domain' => new RawExp(':domain'),
                'path_regex' => new RawExp(':path_regex'),
                'auto_imports_enabled' => new RawExp(':auto_imports_enabled')
            ]);

        $stmt = $query->prepare();

        return $stmt->execute([
            'tenant_id' => $this->getTenantId(),
            'user_guid' => $settings->userGuid,
            'domain' => $settings->domain,
            'path_regex' => $settings->pathRegex,
            'auto_imports_enabled' => (int) $settings->autoImportsEnabled,
        ]);
    }

    private function getTenantId(): int
    {
        return $this->config->get('tenant_id') ?? -1;
    }
}
