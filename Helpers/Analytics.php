<?php
namespace Minds\Helpers;

use Minds\Core;
use Minds\Core\Data;

/**
 * Helper to provide request metrics
 * @todo Avoid static and use proper DI
 */
class Analytics
{
    /**
     * Get MAU or DAU
     * @param  string $metric
     * @param  string $reference - eg. daily, monthly
     * @param  int    $ts        - timestamp
     * @return int
     */
    public static function get($metric = "active", $reference = "day", $ts = null)
    {
        $db = new Core\Data\Call('entities_by_time');
        $ts = self::buildTS($reference, $ts);
        return $db->countRow("analytics:$metric:$reference:$ts");
    }

    /**
     * Increments a metric
     * @param  string     $metric
     * @param  int        $ts        - timestamp
     * @param  mixed|null $user_guid - acting user. Null for current.
     * @return null
     */
    public static function increment($metric = "active", $user_guid = null)
    {
        $phone_number_hash = null;
        if (!$user_guid) {
            $user_guid = Core\Session::getLoggedinUser()->guid;
            $phone_number_hash = Core\Session::getLoggedInUser()->getPhoneNumberHash();
        }

        $platform = isset($_REQUEST['cb']) ? 'mobile' : 'browser';
        if (isset($_REQUEST['platform'])) { //will be the sole method once mobile supports
            $platform = $_REQUEST['platform'];
        }

        //skip if we've cached this hour
        $ts = static::buildTS("hour", time());
        $cacher = Core\Data\cache\factory::build('Redis');
        if ($cacher->get("$platform:$metric:$ts:$user_guid") == true) {
            return;
        }
        $cacher->set("$platform:$metric:$ts:$user_guid", true, 3600);

        /*$db = new Core\Data\Call('entities_by_time');
        $ts = self::buildTS("day", $ts);
        $db->insert("analytics:$metric:day:$ts", [$user_guid => time()]);
        $ts = self::buildTS("month", $ts);
        $db->insert("analytics:$metric:month:$ts", [$user_guid => time()]);*/
        //HACK UNTIL THIS GETS REFACTORED!
        $event = new Core\Analytics\Metrics\Event();
        $event->setType('action')
            ->setAction($metric)
            ->setProduct('platform')
            ->setUserGuid((string) $user_guid)
            ->setPlatform($platform);

        if ($phone_number_hash) {
            $event->setUserPhoneNumberHash($phone_number_hash);
        }

        $event->push();
    }

    /**
     * Gets a timestamp based on a string, and optionally on the
     * passed timestamp.
     * @param  string   $reference
     * @param  int|null $ts        - timestamp. Null for current time.
     * @return int
     */
    public static function buildTS($reference = "day", $ts = null)
    {
        date_default_timezone_set('UTC');
        if (!$ts) {
            $ts = time();
        }
        switch ($reference) {
          case "hour":
            return $ts - ($ts % 3600);
            break;
          case "day":
            $reference = "midnight";
            break;
          case "yesterday":
            $reference = "yesterday";
            break;
          case "month":
            $reference = "midnight first day of this month";
            break;
          case "last-month":
            $reference = "midnight first day of last month";
            break;
        }
        return strtotime($reference, $ts);
    }
}



1258690679576469507
1258694923360673812
1228784289160634386
1231246886640295950
1159515409255243783
1259463981731422219
1259462941426589705
1228780669090406403
1261619287781548049
1261624985668034579
1228781785416998918
1231246100053106695
1261629525721096196
1204148760779694093
1261631146794426381
1231244846795071503
1228779495478009863
1204139020628533255
1259469134689738756
1261621083753160710
1204147997957431316
1204136513512677381
1260246478694129675
1259467708773179396
1259466686239612935
1228783028591927301
1259464779047641097
1261628382995554318
1261623144943198209
1259465937954807813
1204136990753169414
1261626808298643459
1259461674272825345