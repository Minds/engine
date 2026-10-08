<?php
/**
 * Backfill The Confessionals' existing members into their featured groups.
 * DRY RUN by default: only reads, prints counts. Pass --apply to write.
 * Joins only users with no membership (or a pending request). Never touches
 * existing members, moderators, owners or banned users. Sends no emails.
 * Run inside the legacy engine pod, from the engine root (folder with cli.php):
 *   php featured-groups-backfill.php --group=1954946135177256960          (dry run, Slingshot Nation Live)
 *   php featured-groups-backfill.php --group=1954946135177256960 --apply  (write)
 *   php featured-groups-backfill.php                                     (dry run, all featured groups)
 */
if (PHP_SAPI !== 'cli') { exit(1); }
define('__MINDS_ROOT__', getcwd());
require_once getcwd() . '/vendor/autoload.php';
error_reporting(E_ALL); ini_set('display_errors', 1); date_default_timezone_set('UTC');
Dotenv\Dotenv::createImmutable(getcwd())->load();

$minds = new Minds\Core\Minds();
$minds->loadLegacy();

use Minds\Core\Di\Di;
use Minds\Core\Groups\V2\Membership\Enums\GroupMembershipLevelEnum;
use Minds\Core\MultiTenant\Enums\FeaturedEntityTypeEnum;
use Minds\Exceptions\NotFoundException;

Di::_()->get('Security\ACL')->setIgnore(true);

$opts = getopt('', ['domain::', 'group::', 'apply']);
$domain = $opts['domain'] ?? 'app.theconfessionalspodcast.com';
$onlyGroup = isset($opts['group']) ? (int) $opts['group'] : null;
$apply = array_key_exists('apply', $opts);

$tenant = Di::_()->get(Minds\Core\MultiTenant\Services\MultiTenantDataService::class)->getTenantFromDomain($domain);
if (!$tenant) { fwrite(STDERR, "No tenant for $domain\n"); exit(1); }
$tenantId = (int) $tenant->id;
Di::_()->get(Minds\Core\MultiTenant\Services\MultiTenantBootService::class)->bootFromTenantId($tenantId);

$featured = Di::_()->get(Minds\Core\MultiTenant\Services\FeaturedEntityService::class);
$users = Di::_()->get(Minds\Core\MultiTenant\Services\TenantUsersService::class);
$memberships = Di::_()->get(Minds\Core\Groups\V2\Membership\Manager::class);
$builder = Di::_()->get('EntitiesBuilder');

echo ($apply ? 'APPLY' : 'DRY RUN') . " tenant $tenantId ($domain)\n";

foreach ($featured->getAllFeaturedEntities($tenantId, FeaturedEntityTypeEnum::GROUP) as $fg) {
    if (!$fg->autoSubscribe || ($onlyGroup && $fg->entityGuid !== $onlyGroup)) { continue; }
    $group = $builder->single($fg->entityGuid);
    if (!$group instanceof Minds\Entities\Group) { echo "skip {$fg->entityGuid}: not found\n"; continue; }

    $c = ['users' => 0, 'already_in' => 0, 'banned' => 0, 'would_add' => 0, 'added' => 0, 'errors' => 0];
    $before = $memberships->getMembersCount($group);

    foreach ($users->getUsers(tenantId: $tenantId) as $user) {
        $c['users']++;
        try {
            if ($user->isBanned()) { $c['banned']++; continue; }
            try {
                $m = $memberships->getMembership($group, $user);
                if ($m->isBanned()) { $c['banned']++; continue; }
                if (!$m->isAwaiting()) { $c['already_in']++; continue; }
            } catch (NotFoundException $e) {
                // not in the group
            }
            $c['would_add']++;
            if ($apply && $memberships->joinGroup(group: $group, user: $user, membershipLevel: GroupMembershipLevelEnum::MEMBER)) {
                $c['added']++;
            }
        } catch (\Throwable $e) {
            $c['errors']++;
            echo "error user {$user->getGuid()}: {$e->getMessage()}\n";
        }
    }

    echo json_encode(['group' => (string) $fg->entityGuid, 'name' => $group->getName(), 'members_before' => $before] + $c
        + ['members_after' => $memberships->getMembersCount($group)]) . "\n";
}
