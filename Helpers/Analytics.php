<?php
namespace Minds\Helpers;

use Minds\Core;
use Minds\Core\Data;

/**
 * A helper class to provide request metrics
 */
class Analytics
{
    /**
     * Get MAU or DAU
     * @param $metric
     * @param $reference - eg. daily, monthly
     * @param int $ts
     * @return int - the count
     */
    public static function get($metric = "active", $reference = "day", $ts = null)
    {
        $db = new Core\Data\Call('entities_by_time');
        $ts = self::buildTS($reference, $ts);
        return $db->countRow("analytics:$metric:$reference:$ts");
    }

    /**
     * @return void
     */
    public static function increment($metric = "active", $ts = null)
    {
        $db = new Core\Data\Call('entities_by_time');
        $ts = self::buildTS("day", $ts);
        $db->insert("analytics:$metric:day:$ts", array(Core\Session::getLoggedinUser()->guid => time()));
        $ts = self::buildTS("month", $ts);
        $db->insert("analytics:$metric:month:$ts", array(Core\Session::getLoggedinUser()->guid => time()));
    }

    /**
     * Get timestamp to nearest 5 minutes
     */
    public static function buildTS($reference = "day", $ts = null)
    {
        date_default_timezone_set('UTC');
        if (!$ts) {
            $ts = time();
        }
        switch ($reference) {
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
