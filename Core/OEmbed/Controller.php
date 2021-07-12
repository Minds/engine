<?php

/**
 * oEmbed endpoint for returning summary objects of video and images.
 * Specifications: https://oembed.com/
 * @version 1
 */
namespace Minds\Core\OEmbed;

use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;
use Minds\Core\Di\Di;

class Controller
{
    /** @var acl */
    protected $entitiesBuilder;

    /** @var Security\ACL */
    protected $acl;

    /** @var Config */
    protected $config;

    /**
     * Current oEmbed version.
     */
    const OEMBED_VERSION = 1;

    public function __construct(
        $entitiesBuilder = null,
        $acl = null,
        $config = null
    ) {
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
        $this->acl = $acl ?: Di::_()->get('Security\ACL');
        $this->config = $config ?: Di::_()->get('Config');
    }

    /**
     * @param ServerRequest $request
     * @return mixed|null
     */
    public function getOEmbed(ServerRequest $request): JsonResponse
    {
        $queryParams = $request->getQueryParams();

        $params = [
            'url' => filter_var($queryParams['url'] ?? false, FILTER_VALIDATE_URL),
            'format' => filter_var($queryParams['format'] ?? 'json', FILTER_SANITIZE_STRING),
            'maxwidth' => (int) filter_var($queryParams['maxwidth'] ?? '', FILTER_SANITIZE_STRING),
            'maxheight' => (int) filter_var($queryParams['maxheight'] ?? '', FILTER_SANITIZE_STRING),
        ];

        if ($params['format'] !== 'json') {
            return new JsonResponse([
                'status' => 501,
                'message' => 'Unsupported format, only the default format, "json" is currently supported.',
            ]);
        }

        if (!$params['url']) {
            return new JsonResponse([
                'status' => 400,
                'message' => 'This URL appears to be invalid, please ensure that the url is properly encoded.',
            ]);
        }

        $guid = $this->trimGuid($params['url']);
        
        if (!filter_var($guid ?? false, FILTER_VALIDATE_INT)) {
            return new JsonResponse([
                'status' => 400,
                'message' => 'Invalid GUID.',
            ]);
        }

        $entity = $this->entitiesBuilder->single($guid);

        if (!$this->acl->read($entity) || $entity->wire_threshold) {
            return new JsonResponse([
                'status' => 401,
                'messaged' => 'Unauthorized access to resource'
            ]);
        }

        if (!$entity) {
            return new JsonResponse([
                'status' => 404,
                'message' => 'Entity not found.'
            ]);
        }

        // not image or video
        if ($entity->type !== 'object') {
            return new JsonResponse([
                'status' => 501,
                'message' => 'Only image and video links are supported.',
            ]);
        }

        $version = self::OEMBED_VERSION;

        $dimensions = $this->getRestrictedDimensions($entity, $params['maxheight'], $params['maxwidth']);

        $height = $dimensions['height'];
        $width = $dimensions['width'];

        switch ($entity->subtype) {
            case 'video':
                $type = 'video';

                return new JsonResponse([
                    'status' => 'success',
                    'html' => "<iframe src=\"https://www.minds.com/embed/$entity->guid\"></iframe>",
                    'height' => $height,
                    'width' => $width,
                    'type' => $type,
                    'version' => $version,
                    'title' => $entity->getTitle() ?: null,
                    'author_name' => $this->getAuthorName($entity) ?: null,
                    'author_url' => $this->getAuthorUrl($entity) ?: null,
                    'provider_name' => $this->getProviderName() ?: null,
                    'provider_url' => $this->getProviderUrl()  ?: null,
                ]);
                break;
            case 'image':
                $type = 'photo';
                $exportedEntity = $entity->export();
                $url = $exportedEntity['thumbnail_src'] ?: $$exportedEntity['thumbnail'] ?: '';

                if (!$url) {
                    return new JsonResponse([
                        'status' => 500,
                        'message' => 'An unknown error hs occurred.'
                    ]);
                    break;
                }

                return new JsonResponse([
                    'status' => 200,
                    'type' => $type,
                    'version' => $version,
                    'url' => $url,
                    'width' => $width,
                    'height' => $height,
                    'title' => $entity->getTitle() ?: null,
                    'author_name' => $this->getAuthorName($entity) ?: null,
                    'author_url' => $this->getAuthorUrl($entity) ?: null,
                    'provider_name' => $this->getProviderName() ?: null,
                    'provider_url' => $this->getProviderUrl()  ?: null,
                ]);
                break;
            default:
                return new JsonResponse([
                    'status' => 501,
                    'message' => 'Only image and video links are supported.',
                ]);
                break;
        }
    }

    /**
     * Trims GUID from a URL.
     * @param string $url - url to be trimmed.
     * @return string - guid.
     */
    private function trimGuid(string $url): string
    {
        $queryString = explode('newsfeed/', $url)[1];
        return explode('?', $queryString)[0];
    }
    
    /**
     * Gets dimensions restricted by max height and width from an entity.
     * @param $entity - entity to be presented.
     * @param $maxHeight - the maximum height.
     * @param $maxWidth - the maximum width.
     * @return array height and width parameters in an array.
     */
    private function getRestrictedDimensions($entity, $maxHeight, $maxWidth): array
    {
        $height = $entity->height;
        $width = $entity->width;

        if (!$height || !$width) {
            $height = 720;
            $width = 1280;
        }

        if ($maxHeight === 0 && $maxWidth === 0) {
            return [
                'width' => $width,
                'height' => $height
            ];
        }

        if ($maxHeight && $height > $maxHeight) {
            $width = ($width / $height) * $maxHeight;
            $height = $maxHeight;
        }

        if ($maxWidth && $width > $maxWidth) {
            $height = ($height / $width) * $maxWidth;
            $width = $maxWidth;
        }

        return [
            'width' => $width,
            'height' => $height
        ];
    }

    /**
     * Gets author name.
     * @param $entity.
     * @return string author name.
     */
    private function getAuthorName($entity): string
    {
        return $entity->getOwnerEntity()->username;
    }

    /**
     * Gets author url.
     * @param $entity.
     * @return string author url.
     */
    private function getAuthorUrl($entity): string
    {
        return $this->getProviderUrl().$this->getAuthorName($entity);
    }

    /**
     * Gets provider name.
     * @return string provider name.
     */
    private function getProviderName(): string
    {
        return $this->config->get('site_name');
    }

    /**
     * Gets Provider URL
     * @return string - provider url
     */
    private function getProviderUrl(): string
    {
        return $this->config->get('site_url');
    }
}
