<?php
/**
 */

 namespace Minds\Core\Reports;

use Minds\Core;
use Minds\Entities;
use Minds\Helpers;
use Minds\Core\Di\Di;
use Minds\Core\Config;
use Minds\Core\Analytics\Metrics\Event;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Channels\Delegates\Ban;

class Events
{
    public function register()
    {
        Di::_()->get('EventsDispatcher')->register('ban', 'user', function ($event) {
            $config = Di::_()->get('Config');
            $user = $event->getParameters();

            // Record metric
            $event = new Event();
            $event->setType('action')
                ->setProduct('platform')
                ->setUserGuid((string) Core\Session::getLoggedInUser()->guid)
                ->setEntityGuid((string) $user->getGuid())
                ->setAction("ban")
                ->setBanReason($user->ban_reason)
                ->push();
        });
    }

    /**
     * Returns a readable format for a given ban reason, converting
     * tree indices to their text counterparts.
     *
     * e.g. with the default config, an index of 1 returns "Illegal"
     * an index of 1.3 returns "Illegal / Extortion"
     *
     * @param string $index - the given ban reason index
     * @return string the match from the ban reason tree, or
     *  if text is in the reason field, it will return that.
     */
    public function getBanReasons($reason): string
    {
        $banReasons = Di::_()->get('Config')->get('report_reasons');
        $splitReason = preg_split("/\./", $reason);

        if (is_numeric($reason) && isset($splitReason[0])) {
            // get filter out matching reason and re-index array from 0.
            $reasonObject = array_values(array_filter(
                $banReasons,
                function ($r) use ($splitReason) {
                    return (string) $r['value'] === $splitReason[0];
                }
            ));
            // start string with matching label
            $reasonString = $reasonObject[0]['label'];
            // if has more, and the reason supplied requests more (e.g. 1.1)
            if ($reasonObject[0]['hasMore'] && isset($splitReason[1])) {
                // filter for sub-reasons.
                $subReasonObject = array_values(array_filter(
                    $reasonObject[0]['reasons'],
                    function ($sub) use ($splitReason) {
                        return (string) $sub['value'] === $splitReason[1];
                    }
                ));
                $reasonString .= ' / '.$subReasonObject[0]['label'];
            }
            return $reasonString;
        }
        return $reason;
    }
}
