<?php
/**
 * READ-ONLY growth check for The Confessionals (Vera, 2026-10-08).
 * Only SELECTs. Prints counts and settings, never emails, names of members,
 * keys or secrets. Run inside the legacy engine pod, from the engine root
 * (the folder with cli.php):
 *   php tenant-growth.php
 *   php tenant-growth.php --domain=app.theconfessionalspodcast.com
 */
if (PHP_SAPI !== 'cli') { exit(1); }
define('__MINDS_ROOT__', getcwd());
require_once getcwd() . '/vendor/autoload.php';
error_reporting(E_ALL); ini_set('display_errors', 1); date_default_timezone_set('UTC');
Dotenv\Dotenv::createImmutable(getcwd())->load();

$minds = new Minds\Core\Minds();
$minds->loadLegacy();

use Minds\Core\Di\Di;

$opts = getopt('', ['domain::']);
$domain = $opts['domain'] ?? 'app.theconfessionalspodcast.com';
$pdo = Di::_()->get('Database\MySQL\Client')->getConnection(Minds\Core\Data\MySQL\Client::CONNECTION_REPLICA);

function q(PDO $pdo, string $label, string $sql, array $args = []): void {
    try {
        $st = $pdo->prepare($sql);
        $st->execute($args);
        echo "== $label\n";
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) { echo json_encode($row) . "\n"; }
    } catch (\Throwable $e) {
        echo "== $label: FAILED " . $e->getMessage() . "\n";
    }
}

// 1. Every live tenant on the engine (Mark: other tenants need a move or notice too).
$st = $pdo->prepare("SELECT t.tenant_id FROM minds_tenants t
    LEFT JOIN minds_tenants_domain_details d ON d.tenant_id = t.tenant_id
    WHERE t.domain = :d OR d.domain = :d LIMIT 1");
$st->execute(['d' => $domain]);
$tid = $st->fetchColumn();
if (!$tid) { echo "tenant not found for $domain\n"; exit(1); }
$a = ['t' => $tid];
echo "tenant_id $tid\n";

q($pdo, '1 signups by month (last 24 months)', "SELECT DATE_FORMAT(time_created, '%Y-%m') AS month, COUNT(*) AS signups
    FROM minds_entities_user WHERE tenant_id = :t AND time_created >= NOW() - INTERVAL 24 MONTH
    GROUP BY month ORDER BY month", $a);
q($pdo, '2 accounts total / before 24 months', "SELECT COUNT(*) AS total, SUM(time_created < NOW() - INTERVAL 24 MONTH) AS older,
    SUM(deleted = 1 OR enabled = 0 OR banned = 1) AS inactive_flags FROM minds_entities_user WHERE tenant_id = :t", $a);
q($pdo, '3 active by last login', "SELECT SUM(last_login >= NOW() - INTERVAL 7 DAY) AS wau,
    SUM(last_login >= NOW() - INTERVAL 30 DAY) AS mau,
    SUM(last_login >= NOW() - INTERVAL 90 DAY) AS active_90d,
    SUM(last_login IS NULL) AS never_logged
    FROM minds_entities_user WHERE tenant_id = :t", $a);
q($pdo, '4 last login month (last 12 months)', "SELECT DATE_FORMAT(last_login, '%Y-%m') AS month, COUNT(*) AS users_last_seen
    FROM minds_entities_user WHERE tenant_id = :t AND last_login >= NOW() - INTERVAL 12 MONTH
    GROUP BY month ORDER BY month", $a);
q($pdo, '5 paid memberships started by month (last 24 months)', "SELECT DATE_FORMAT(valid_from, '%Y-%m') AS month,
    COUNT(*) AS started, SUM(valid_to IS NULL OR valid_to > NOW()) AS still_active
    FROM minds_site_membership_subscriptions WHERE tenant_id = :t AND valid_from >= NOW() - INTERVAL 24 MONTH
    GROUP BY month ORDER BY month", $a);
q($pdo, '6 paid members active now', "SELECT COUNT(DISTINCT user_guid) AS active_members
    FROM minds_site_membership_subscriptions WHERE tenant_id = :t AND (valid_to IS NULL OR valid_to > NOW())", $a);
