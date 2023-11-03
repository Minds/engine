<?php

namespace Minds\Core\Http\Cloudflare\Enums;

enum CustomHostnameStatusEnum: string
{
    case ACTIVE = 'active';
    case PENDING = 'pending';
    case ACTIVE_REDEPLOYING = 'active_redeploying';
    case MOVED = 'moved';
    case PENDING_DELETION = 'pending_deletion';
    case DELETED = 'deleted';
    case PENDING_BLOCKED = 'pending_blocked';
    case PENDING_MIGRATION = 'pending_migration';
    case PENDING_PROVISIONED = 'pending_provisioned';
    case TEST_PENDING = 'test_pending';
    case TEST_ACTIVE = 'test_active';
    case TEST_ACTIVE_APEX = 'test_active_apex';
    case TEST_BLOCKED = 'test_blocked';
    case TEST_FAILED = 'test_failed';
    case PROVISIONED = 'provisioned';
    case BLOCKED = 'blocked';
}
