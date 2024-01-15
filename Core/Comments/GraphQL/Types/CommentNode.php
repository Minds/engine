<?php
declare(strict_types=1);

namespace Minds\Core\Comments\GraphQL\Types;

use Minds\Core\Comments\Comment;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Entities\GraphQL\Types\AttachmentNode;
use Minds\Core\EntitiesBuilder;
use TheCodingMachine\GraphQLite\Annotations\Type;
use Minds\Core\Feeds\GraphQL\Types\AbstractEntityNode;
use Minds\Core\Feeds\GraphQL\Types\UserNode;
use Minds\Core\Log\Logger;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\Field;

/**
 * The CommentNode returns relevant information about the Comment.
 */
#[Type]
class CommentNode extends AbstractEntityNode
{
    public function __construct(
        protected Comment $comment,
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?Config $config = null,
        private ?Logger $logger = null,
    ) {
        $this->entity = $comment;
        $this->entitiesBuilder ??= Di::_()->get(EntitiesBuilder::class);
        $this->config ??= Di::_()->get(Config::class);
    }

    #[Field]
    public function getOwner(): UserNode
    {
        /** @var User */
        $owner = $this->entitiesBuilder->single($this->comment->getOwnerGuid());
        return new UserNode($owner);
    }

    /**
     * Still used for votes, to be removed soon
     */
    #[Field]
    public function getLuid(): string
    {
        return (string) $this->comment->getLuid();
    }

    #[Field]
    public function getParentPath(): string
    {
        return $this->comment->getParentPath();
    }

    #[Field]
    public function getChildPath(): string
    {
        return $this->comment->getChildPath();
    }

    #[Field]
    public function getBody(): string
    {
        return $this->comment->getBody();
    }

    #[Field]
    public function getUrl(): string
    {
        return $this->config->get('site_url') . 'newsfeed/' . $this->comment->getEntityGuid() . '?focusedCommentUrn=' . $this->comment->getUrn();
    }

    /**
     * Gets a comments linked AttachmentNode.
     * @return AttachmentNode|null The AttachmentNode or null if there is no attachment.
     */
    #[Field]
    public function getAttachment(): ?AttachmentNode
    {
        $attachments = $this->comment->getAttachments();

        if (!isset($attachments['custom_data'])) {
            return null;
        }

        try {
            $customData = json_decode($attachments['custom_data'], true) ?? null;

            if (!$customData) {
                return null;
            }

            return new AttachmentNode(
                guid: $customData['guid'],
                containerGuid: $customData['container_guid'],
                type: $attachments['custom_type'],
                src: $customData['src'],
                href: $customData['href'],
                mature: $customData['mature'] ?? false,
                width: $customData['width'] ?? null,
                height: $customData['height'] ?? null
            );
        } catch (\Exception $e) {
            $this->logger->error($e);
            return null;
        }
    }
}
