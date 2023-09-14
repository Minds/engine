<?php
namespace Minds\Entities\Enums;

enum FederatedEntitySourcesEnum: string
{
    case LOCAL = 'local';
    case ACTIVITY_PUB = 'activitypub';
    case NOSTR = 'nostr';
}
