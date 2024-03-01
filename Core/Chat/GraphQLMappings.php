<?php
declare(strict_types=1);

namespace Minds\Core\Chat;

use Minds\Core\GraphQL\AbstractGraphQLMappings;

class GraphQLMappings extends AbstractGraphQLMappings
{
    public function register(): void
    {
        $this->schemaFactory->addControllerNamespace('Minds\Core\Chat\\Controllers');
        $this->schemaFactory->addTypeNamespace('Minds\\Core\\Chat\\Enums');
        $this->schemaFactory->addTypeNamespace('Minds\Core\Chat\Types');
        // $this->schemaFactory->addTypeMapperFactory(new StaticClassListTypeMapperFactory([
        //     // Messages
        //     Types\ChatMessageEdge::class,
        //     Types\ChatMessageNode::class,
        //     Types\ChatMessagesConnection::class,
        //     // Rooms
        //     Types\ChatRoomEdge::class,
        //     Types\ChatRoomNode::class,
        //     Types\ChatRoomsConnection::class,
        //     // Room members
        //     Types\ChatRoomMemberEdge::class,
        //     Types\ChatRoomMembersConnection::class,
        // ]));
    }
}
