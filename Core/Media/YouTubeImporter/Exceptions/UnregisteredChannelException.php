<?php
/**
 * @author: eiennohi.
 */

namespace Minds\Core\Media\YouTubeImporter\Exceptions;

class UnregisteredChannelException extends \Exception
{
    public function __construct()
    {
        parent::__construct('channelId is not registered to this user');
    }
}
