<?php
/**
 * Abuse Guard Accused
 */
namespace Minds\Core\Security\AbuseGuard;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Entities;

class AccusedEntity
{
    /** @var Core\Data\ElasticSearch\Client */
    private $client;

    /** @var Entites\User */
    private $user;

    /** @var int */
    private $score = 0;

    /** @var array */
    private $metrics = [];

    public function __construct($client = null)
    {
        $this->client = $client ?: Di::_()->get('Database\ElasticSearch');
    }

    public function setUserGuid($guid)
    {
        $this->user = new Entities\User($guid);
        return $this;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setMetric($metric, $count)
    {
        $this->metrics[$metric] = $count;
        return $this;
    }

    public function setScore($score)
    {
        $this->score = $score;
        return $this;
    }

    public function getScore()
    {
        $this->score = 0;
        $oneHourAgo = 60 * 60;
        $multiplier = 0;
        if ($this->user->time_created > time() - $oneHourAgo) {
            $multiplier = 1;
        }
        foreach ($this->metrics as $count) {
            $this->score += $count;
        }
        //echo "\n{$this->user->guid} ($this->score * $multiplier)";
        return $this->score * $multiplier;
    }
}
