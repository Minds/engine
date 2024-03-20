<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\CustomPages;

use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\MultiTenant\CustomPages\Enums\CustomPageTypesEnum;
use Minds\Core\MultiTenant\CustomPages\Types\CustomPage;
use Minds\Core\MultiTenant\CustomPages\Defaults\CustomPageDefaultContent;
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
        $tenantId = $this->config->get('tenant_id');

        $statement = $this->mysqlClientReaderHandler->select()
            ->from('minds_custom_pages')
            ->where('page_type', Operator::EQ, new RawExp(":page_type"))
            ->where('tenant_id', Operator::EQ, $tenantId)
            ->prepare();

        if (!$statement->execute(['page_type' => $pageType->value])) {
            throw new ServerErrorException("Failed to fetch custom page");
        }

        if ($statement->rowCount() === 0) {
            return new CustomPage(
                pageType: $pageType,
                content: null,
                externalLink: null,
                defaultContent: CustomPageDefaultContent::get()[$pageType->value] ?? null,
                tenantId: $tenantId
            );
        }

        return $this->buildCustomPage($statement->fetch(PDO::FETCH_ASSOC));
    }

    /**
     * Inserts or updates a custom page in the database.
     *
     * @param CustomPageTypesEnum $pageType The type of the custom page.
     * @param string|null $content The content of the custom page.
     * @param string|null $externalLink The external link of the custom page.
     * @return bool
     */
    public function setCustomPage(CustomPageTypesEnum $pageType, ?string $content, ?string $externalLink): bool
    {
        $query = $this->mysqlClientWriterHandler->insert()
            ->into('minds_custom_pages')
            ->set([
                'tenant_id' => new RawExp(':tenant_id'),
                'page_type' => new RawExp(':page_type'),
                'content' => new RawExp(':content'),
                'external_link' => new RawExp(':external_link')
            ])
            ->onDuplicateKeyUpdate([
                'content' => new RawExp(':content'),
                'external_link' => new RawExp(':external_link')
            ]);


        $stmt = $query->prepare();

        return $stmt->execute([
            'tenant_id' => $this->config->get('tenant_id'),
            'page_type' => $pageType->value,
            'content' => $content,
            'external_link' => $externalLink
        ]);
    }

    /**
     * Builds a CustomPage object from database row data.
     *
     * @param array $row The database row data.
     * @return CustomPage The custom page.
     */
    private function buildCustomPage(array $row): CustomPage
    {
        $pageType = CustomPageTypesEnum::from($row['page_type']);
        $defaultContent = CustomPageDefaultContent::get()[$pageType->value] ?? null;

        return new CustomPage(
            pageType: $pageType,
            content: $row['content'] ?? null,
            externalLink: $row['external_link'] ?? null,
            defaultContent: $defaultContent,
            tenantId: $row['tenant_id'],
        );
    }
}
