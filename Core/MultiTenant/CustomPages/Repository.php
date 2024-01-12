<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\CustomPages;

use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\MultiTenant\CustomPages\Enums\CustomPageTypesEnum;
use Minds\Core\MultiTenant\CustomPages\Types\CustomPage;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Selective\Database\Operator;
use Selective\Database\RawExp;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;
use PDO;

/**
 * MultiTenant CustomPages repository
 */
class Repository extends AbstractRepository
{
    public function __construct(
        ... $args
    ) {
        parent::__construct(...$args);
    }

    /**
     * Retrieves a custom page based on the page type and tenant ID.
     *
     * @param CustomPageTypesEnum $pageType The type of the custom page to retrieve.
     * @return CustomPage|null The custom page if found, null otherwise.
     * @throws NotFoundException if the custom page is not found.
     * @throws ServerErrorException if there is a server error.
     */
    public function getCustomPageByType(CustomPageTypesEnum $pageType): ?CustomPage
    {
        $statement = $this->mysqlClientReaderHandler->select()
            ->from('minds_custom_pages')
            ->where('page_type', Operator::EQ, new RawExp(":page_type"))
            ->where('tenant_id', Operator::EQ, $this->config->get('tenant_id') ?? -1)
            ->prepare();

        if (!$statement->execute(['page_type' => $pageType->value])) {
            throw new ServerErrorException("Failed to fetch custom page");
        }

        if ($statement->rowCount() === 0) {
            throw new NotFoundException("Custom page not found");
        }

        return $this->buildCustomPage($statement->fetch(PDO::FETCH_ASSOC));
    }

    /**
     * Inserts or updates a custom page in the database.
     *
     * @param CustomPageTypesEnum $pageType The type of the custom page.
     * @param string|null $content The content of the custom page.
     * @param string|null $externalLink The external link of the custom page.
     * @return CustomPage The instance of the newly created or updated custom page.
     */
    public function setCustomPage(CustomPageTypesEnum $pageType, ?string $content, ?string $externalLink): CustomPage
    {
        $stmt = $this->mysqlClientWriterHandler->prepare("REPLACE INTO minds_custom_pages (tenant_id, page_type, content, external_link) VALUES (:tenant_id, :page_type, :content, :external_link)");
        $stmt->execute([
            ':tenant_id' => $this->config->get('tenant_id'),
            ':page_type' => $pageType->value,
            ':content' => $content,
            ':externalLink' => $externalLink
        ]);

        return new CustomPage(
            pageType: $pageType,
            content: $content,
            externalLink: $externalLink
        );
    }

    /**
     * Builds a CustomPage object from database row data.
     *
     * @param array $row The database row data.
     * @return CustomPage The custom page.
     */
    private function buildCustomPage(array $row): CustomPage
    {
        $tenantId = $this->config->get('tenant_id');

        return new CustomPage(
            pageType: CustomPageTypesEnum::from((int)$row['page_type']),
            content: $row['content'] ?? '',
            externalLink: $row['external_link'] ?? '',
             tenantId: $tenantId
        );
    }
}