<?php
namespace Minds\Core\Comments\EmbeddedComments\Services;

use Exception;
use GuzzleHttp\Exception\ClientException;
use Minds\Common\Access;
use Minds\Core\Comments\EmbeddedComments\Exceptions\InvalidScrapeException;
use Minds\Core\Comments\EmbeddedComments\Repositories\EmbeddedCommentsRepository;
use Minds\Core\Config\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\Activity;
use Minds\Core\Feeds\Activity\Manager as ActivityManager;
use Minds\Core\Feeds\Activity\RichEmbed\Metascraper\Service as MetascraperService;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Core\Security\ACL;
use Minds\Entities\Enums\FederatedEntitySourcesEnum;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use PDOException;

class EmbeddedCommentsActivityService
{
    private int $ownerGuid;
    private string $url;

    public function __construct(
        private EmbeddedCommentsRepository $repository,
        private Config $config,
        private ACL $acl,
        private EntitiesBuilder $entitiesBuilder,
        private MetascraperService $metaScraperService,
        private ActivityManager $activityManager,
        private Logger $logger,
    ) {
        
    }

    /**
     * Set the owner of the embedded comments widget
     */
    public function withOwnerGuid(int $ownerGuid): EmbeddedCommentsActivityService
    {
        $instance = clone $this;
        $instance->ownerGuid = $ownerGuid;
        return $instance;
    }

    /**
     * Sets the url the embedded comments widget is pointing to
     */
    public function withUrl(string $url): EmbeddedCommentsActivityService
    {
        $instance = clone $this;
        $instance->url = $url;
        return $instance;
    }

    /**
     * Returns an activity post from a url.
     * If an activity post can not be found, we will create a new one
     */
    public function getActivityFromUrl(bool $import = true): Activity
    {
        $guid = $this->repository->getActivityGuidFromUrl($this->url, $this->ownerGuid);

        if ($guid) {
            $activity = $this->entitiesBuilder->single($guid);
            return $activity;
        }

        ////
        // No activity post was found
        ////

        if (!$import) {
            throw new Exception("Activity post was not found");
        }

        // Does the url match our approved path pattern?
        if (!$this->isApprovedUrl()) {
            throw new \Exception("Invalid url pattern");
        }

        $activity = $this->proccessActivity();

        return $activity;
    }

    /**
     * Checks if the url provided matches a path pattern that has been defined
     */
    private function isApprovedUrl(): bool
    {
        $urlParts = parse_url($this->url);

        return true;
    }

    /**
     * Imports the post as a rich embed, and links so we dont import again
     */
    private function proccessActivity(): Activity
    {
        $ia = $this->acl->setIgnore(true);

        $activity = new Activity();
        $activity->setAccessId(Access::PUBLIC);
        $activity->setSource(FederatedEntitySourcesEnum::LOCAL);

        $owner = $this->getOwner();
        $activity->container_guid = $owner->guid;
        $activity->owner_guid = $owner->guid;
        $activity->ownerObj = $owner->export();

        $canonicalUrl = '';

        // Prepare the activity with the rich embed
        try {
            $richEmbed = $this->metaScraperService->scrape($this->url);

            $canonicalUrl = $richEmbed['meta']['canonical_url'];

            if ($canonicalUrl !== $this->url) {
                // The canonical url does not match, abort the import and try to fetch the activity with the
                // correct canonical url
                return $this->getActivityFromUrl($canonicalUrl);
            }

            $activity
                ->setTitle($richEmbed['meta']['title'])
                ->setBlurb($richEmbed['meta']['description'])
                ->setURL($canonicalUrl)
                ->setThumbnail($richEmbed['links']['thumbnail'][0]['href']);
        } catch (ServerErrorException $e) {
            // Client exception signals issue at remote end, not ours
            $this->logger->info($e->getMessage());

            throw new InvalidScrapeException();
        }
    
        // Save the activity post
        $this->activityManager->add($activity);

        // Pair the activity post to the url
        try {
            $this->repository->addActivityGuidWithUrl(
                guid: (int) $activity->getGuid(),
                url: $canonicalUrl,
                userGuid: $this->ownerGuid
            );
        } catch (PDOException $e) {
            // If duplicate, then delete the post we made
            // There was probably a race condition
            if ($e->getCode() === "23000") {
                $this->activityManager->delete($activity);
                return $this->getActivityFromUrl(import: false);
            }

            // Rethrow
            throw $e;
        }
        
        // Reset the ACL
        $this->acl->setIgnore($ia);

        return $activity;
    }

    /**
     * Returns the owner of the Activity post.
     */
    private function getOwner(): User
    {
        $user = $this->entitiesBuilder->single($this->ownerGuid);

        if (!$user instanceof User) {
            throw new Exception("Invalid root user");
        }

        return $user;
    }
}
