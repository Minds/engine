<?php
/**
 * This subscription will listen for changes in entities (create, update, delete) and update elasticsearch
 * You can test by running `php cli.php EventStreams --subscription=Core\\Search\\RemoteSearchIndexerSubscription`
 */
namespace Minds\Core\Search;

use Minds\Core\Search\SearchIndexerSubscription as SearchSearchIndexerSubscription;

class RemoteSearchIndexerSubscription extends SearchSearchIndexerSubscription
{
    /**
     * @return string
     */
    public function getSubscriptionId(): string
    {
        return 'remote-search-indexer';
    }
}
