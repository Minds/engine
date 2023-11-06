<?php

/**
 * Minds Blog Manager
 *
 * @author emi
 */

namespace Minds\Core\Blogs;

use Minds\Core\Di\Di;
use Minds\Core\Entities\PropagateProperties;
use Minds\Core\Security\ACL;
use Minds\Core\Security\Spam;
use Minds\Core\Security\SignedUri;
use Minds\Core\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Log\Logger;
use Minds\Helpers\Url;

class Manager
{
    /** @var Repository */
    protected $repository;

    /** @var Delegates\Slug */
    protected $slug;

    /** @var Delegates\Feeds */
    protected $feeds;

    /** @var Spam * */
    protected $spam;

    /** @var PropagateProperties */
    protected $propagateProperties;

    /** @var SignedUri $signedUri */
    private $signedUri;

    /** @var Config $config */
    private $config;

    /**
     * Manager constructor.
     * @param null $repository
     * @param null $slug
     * @param null $feeds
     * @param null $spam
     * @param PropagateProperties $propagateProperties
     * @throws \Exception
     */
    public function __construct(
        $repository = null,
        $slug = null,
        $feeds = null,
        $spam = null,
        PropagateProperties $propagateProperties = null,
        $signedUri = null,
        $config = null,
        protected ?EventsDispatcher $eventsDispatcher = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?Logger $logger = null
    ) {
        $this->repository = $repository ?: new Repository();
        $this->slug = $slug ?: new Delegates\Slug();
        $this->feeds = $feeds ?: new Delegates\Feeds();
        $this->spam = $spam ?: Di::_()->get('Security\Spam');
        $this->propagateProperties = $propagateProperties ?? Di::_()->get('PropagateProperties');
        $this->signedUri = $signedUri ?? new SignedUri;
        $this->config = $config ?? Di::_()->get('Config');
        $this->eventsDispatcher ??= Di::_()->get('EventsDispatcher');
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->logger ??= Di::_()->get('Logger');
    }

    /**
     * Gets a blog. Migrates the GUID is necessary.
     * @param int $guid
     * @return Blog
     */
    public function get($guid)
    {
        if (strlen($guid) < 15) {
            $guid = (new \GUID())->migrate($guid);
        }

        return $this->repository->get($guid);
    }

    /**
     * Returns next blog.
     * @param Blog $blog
     * @param string $strategy
     * @return Blog|null
     * @throws \Exception
     */
    public function getNext(Blog $blog, $strategy = 'owner')
    {
        switch ($strategy) {
            case 'owner':
                $blogs = $this->repository->getList([
                    'gt' => $blog->getGuid(),
                    'limit' => 1,
                    'user' => $blog->getOwnerGuid(),
                    'reversed' => false,
                ]);
                break;

            default:
                throw new \Exception('Unknown next strategy');
        }

        if (!$blogs || !isset($blogs[0])) {
            return null;
        }

        return $blogs[0];
    }

    /**
     * Adds a blog
     * @param Blog $blog
     * @return int
     * @throws \Exception
     */
    public function add(Blog $blog)
    {
        if ($this->spam->check($blog)) {
            return false;
        }

        $blog
            ->setTimeCreated($blog->getTimeCreated() ?: time())
            ->setTimeUpdated(time())
            ->setLastUpdated(time())
            ->setLastSave(time());

        $this->slug->generate($blog);

        $saved = $this->repository->add($blog);

        if ($saved) {
            if (!$blog->isDeleted()) {
                $this->feeds->index($blog);
                $this->feeds->dispatch($blog);
            }

            $this->propagateProperties->from($blog);

            $this->eventsDispatcher->trigger('entities-ops', 'create', [
                'entityUrn' => $blog->getUrn(),
            ]);
        }

        return $saved;
    }

    /**
     * Updates a blog
     * @param Blog $blog
     * @return int
     * @throws \Exception
     */
    public function update(Blog $blog)
    {
        $shouldReindex = $blog->isDirty('deleted');

        $blog
            ->setTimeUpdated(time())
            ->setLastUpdated(time())
            ->setLastSave(time());

        $this->slug->generate($blog);

        $saved = $this->repository->update($blog);

        if ($saved) {
            if ($shouldReindex) {
                if (!$blog->isDeleted()) {
                    $this->feeds->index($blog);
                } else {
                    $this->feeds->remove($blog);
                }
            }

            $this->propagateProperties->from($blog);

            $this->eventsDispatcher->trigger('entities-ops', 'update', [
                'entityUrn' => $blog->getUrn(),
            ]);
        }

        return $saved;
    }

    /**
     * Deletes a blog
     * @param Blog $blog
     * @return bool
     * @throws \Exception
     */
    public function delete(Blog $blog)
    {
        $deleted = $this->repository->delete($blog);

        if ($deleted) {
            $this->feeds->remove($blog);

            $this->eventsDispatcher->trigger('entities-ops', 'delete', [
                'entityUrn' => $blog->getUrn(),
                'entity' => $blog,
            ]);
        }

        return $deleted;
    }

    /**
     * Add signed uri to blog image srcs
     * so they can be viewed when logged out
     * @param Blog $blog - blog to add signed URIs to body/description of.
     * @return string updated body/description.
     */
    public function signImages(Blog $blog): string
    {
        $desc = $blog->getBody();
        $cdnUrl = $this->config->get('cdn_url');
        $siteUrl = $this->config->get('site_url');

        $dom = new \DOMDocument();

        // Add a fake root element and encoding for HTML parser
        $dom->loadHTML('<?xml encoding="utf-8" ?><div>' . $desc . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $images = $dom->getElementsByTagName('img');

        if (count($images) < 1) {
            return $desc;
        }

        foreach ($images as $image) {
            // strip any existing jwtsig from the URL, so we can reapply it.
            $oldSrc = Url::stripQueryParameter($image->getAttribute('src'), 'jwtsig');
            $srcIsMinds = strpos($oldSrc, $siteUrl) === 0 || strpos($oldSrc, $cdnUrl) === 0 ;

            $entityGuids = [];
            if (
                preg_match('/\/(\d+)\//', $oldSrc, $entityGuids) &&
                !$this->isBlogOwnerImageOwner($blog, $entityGuids[1])
            ) {
                continue;
            }

            if ($srcIsMinds) {
                $newSrc = $this->signedUri->sign($oldSrc);
                $image->setAttribute('src', $newSrc);
            }
        }

        // Don't export fake root element
        $root = $dom->documentElement;
        $result = '';
        foreach ($root->childNodes as $childNode) {
            $result .= $dom->saveHTML($childNode);
        }

        return $result;
    }

    /**
     * Returns true if blog owner is owner of an image by its GUID.
     * @param Blog $blog - Blog to check.
     * @param string $entityGuid - GUID of image entity.
     * @return bool - True when blog owner is owner of image.
     */
    private function isBlogOwnerImageOwner(Blog $blog, string $entityGuid): bool
    {
        try {
            $imageEntity = $this->entitiesBuilder->single($entityGuid);

            if (!$imageEntity) {
                $this->logger->warning(
                    'No image entity found with guid: ' . $entityGuid .
                    ' for blog: ' . $blog->getGuid()
                );
                return false;
            }

            if ($imageEntity->getOwnerGuid() !== $blog->getOwnerGuid()) {
                $this->logger->warning(
                    'Blog owner: ' . $blog->getOwnerGuid() .
                    ' does not own image: '. $entityGuid .
                    ' so cannot insert it into their blog: ' . $blog->getGuid()
                );
                return false;
            }

            return true;
        } catch(\Exception $e) {
            $this->logger->error($e);
            return false;
        }
    }
}
