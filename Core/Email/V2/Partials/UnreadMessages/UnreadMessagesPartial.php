<?php
declare(strict_types=1);

namespace Minds\Core\Email\V2\Partials\UnreadMessages;

use Minds\Core\Chat\Entities\ChatRoomListItem;
use Minds\Core\Chat\Enums\ChatRoomMemberStatusEnum;
use Minds\Core\Chat\Enums\ChatRoomTypeEnum;
use Minds\Core\Chat\Services\RoomService;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\V2\Partials\ActionButtonV2\ActionButtonV2;
use Minds\Core\Guid;
use Minds\Core\Log\Logger;
use Minds\Entities\User;
use Minds\Helpers\Text;
use Minds\Traits\MagicAttributes;

/**
 * Unread messages partial - can be inserted into any email.
 * @method self setUser(User $user)
 * @method self setCreatedAfterTimestamp(int $createdAfterTimestamp)
 */
class UnreadMessagesPartial extends Template
{
    use MagicAttributes;

    /** @var User */
    protected User $user;

    /** @var int */
    protected int $createdAfterTimestamp;

    public function __construct(
        private ?RoomService $chatRoomService = null,
        private ?Logger $logger = null
    ) {
        $this->chatRoomService ??= Di::_()->get(RoomService::class);
        $this->config ??= Di::_()->get(Config::class);
        $this->logger ??= Di::_()->get('Logger');

        parent::__construct();
    }

    public function withArgs(User $user, int $createdAfterTimestamp): self
    {
        $instance = clone $this;
        $instance->user = $user;
        $instance->createdAfterTimestamp = $createdAfterTimestamp;
        return $instance;
    }

    /**
     * Build unread messages partial.
     * @return string|null - unread messages partial.
     */
    public function build()
    {
        $this->loadFromFile = false;
        $this->setTemplate('./template.tpl');

        try {
            $unreadChatRoomListItems = $this->chatRoomService->getUnreadChatRooms(
                user: $this->user,
                limit: 12,
                activeSinceTimestamp: $this->createdAfterTimestamp ?? strtotime('-24 hour')
            );

            if (!$unreadChatRoomListItems || !count($unreadChatRoomListItems)) {
                return;
            }

            $siteUrl = $this->config->get('site_url') ?? 'https://www.minds.com/';

            $unreadChatRoomListItems = $this->sortByActiveFirst($unreadChatRoomListItems);

            $chatRoomData = array_map(fn ($chatRoomListItem) => [
                    'avatar_urls' => $this->getChatRoomAvatarUrls($chatRoomListItem),
                    'room_url' => $siteUrl . 'chat/rooms/' . $chatRoomListItem?->chatRoom?->guid,
                    'name' => Text::truncate($chatRoomListItem?->chatRoom?->name, 23)
            ], $unreadChatRoomListItems);

            $this->set('unreadChatRooms', $chatRoomData);
            $this->set('unreadIconColor', $this->getPrimaryAccentColor());
        
            $viewInChatActionButton = (new ActionButtonV2())
                ->setLabel('View in chat')
                ->setPath($siteUrl . 'chat/rooms');

            $this->set('viewInChatActionButton', $viewInChatActionButton->build());
        } catch (\Exception $e) {
            $this->logger->error($e);
            return null;
        }

        return $this->render();
    }

    /**
     * Get chat room avatar URLs. In future this can support returning multiple avatars
     * for multi-user rooms.
     * @param ChatRoomListItem $chatRoomListItem - chat room list item.
     * @return array - chat room avatar URLs.
     */
    private function getChatRoomAvatarUrls(
        ChatRoomListItem $chatRoomListItem
    ): array {
        if(!$cdnUrl = $this->config->get('cdn_url')) {
            $cdnUrl = 'http://www.minds.com/';
        }


        if ($chatRoomListItem->chatRoom->roomType === ChatRoomTypeEnum::GROUP_OWNED) {
            $guid = $chatRoomListItem->chatRoom->groupGuid ?? $chatRoomListItem->chatRoom->guid;
            return [$cdnUrl . 'fs/v1/avatars/' . $guid . '/large/' . time()];
        }

        $otherMemberGuids = array_values(array_filter(
            $chatRoomListItem->memberGuids,
            fn ($memberGuid) => $this->user->getGuid() !== $memberGuid
        ));

        if (!count($otherMemberGuids)) {
            return [$cdnUrl . 'icon/' . $this->user->getGuid() . '/large/' . time()];
        }

        // Currently, we are not supporting multi-user chat room avatars.
        return [$cdnUrl . 'icon/' . $otherMemberGuids[0] . '/large/' . time()];
    }

    /**
     * Gets primary accent color.
     * @return string - primary accent color.
     */
    private function getPrimaryAccentColor(): string
    {
        if ((bool) $this->config->get('tenant_id')) {
            $themeOverride = $this->config->get('theme_override');

            if (isset($themeOverride['primary_color'])) {
                return $themeOverride['primary_color'];
            }
        }
        return '#1b85d6';
    }

    /**
     * Sorts chat room list items such that active items are first.
     * @param array $unreadChatRoomListItems - unread chat room list items.
     * @return array - sorted chat room list items.
     */
    private function sortByActiveFirst(array $unreadChatRoomListItems): array
    {
        try {
            $sortOrder = [
                ChatRoomMemberStatusEnum::ACTIVE->name => 1,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name => 2,
            ];

            usort($unreadChatRoomListItems, function ($a, $b) use ($sortOrder) {
                return $sortOrder[$a->status->name] <=> $sortOrder[$b->status->name];
            });
        } catch(\Exception $e) {
            $this->logger->error($e);
        }

        return $unreadChatRoomListItems;
    }
}
