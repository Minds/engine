<?php

/**
 * Votes Manager
 *
 * @author emi
 */

namespace Minds\Core\Votes;

use Minds\Core\Captcha\FriendlyCaptcha\Classes\DifficultyScalingType;
use Minds\Core\Captcha\FriendlyCaptcha\Exceptions\InvalidSolutionException;
use Minds\Core\Captcha\FriendlyCaptcha\Exceptions\PuzzleExpiredException;
use Minds\Core\Captcha\FriendlyCaptcha\Exceptions\PuzzleReusedException;
use Minds\Core\Captcha\FriendlyCaptcha\Exceptions\SignatureMismatchException;
use Minds\Core\Captcha\FriendlyCaptcha\Exceptions\SolutionAlreadySeenException;
use Minds\Core\Captcha\FriendlyCaptcha\Manager as FriendlyCaptchaManager;
use Minds\Core\Di\Di;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Core\Router\Exceptions\UnverifiedEmailException;
use Minds\Core\Security\ACL;
use Minds\Entities\User;
use Minds\Exceptions\StopEventException;

class Manager
{
    protected $counters;
    protected $indexes;

    protected $entity;
    protected $actor;

    protected $acl;

    /** @var Dispatcher */
    protected $eventsDispatcher;

    private User $user;

    /**
     * Manager constructor.
     */
    public function __construct(
        $counters = null,
        $indexes = null,
        $acl = null,
        $eventsDispatcher = null,
        private ?FriendlyCaptchaManager $friendlyCaptchaManager = null,
        private ?ExperimentsManager $experimentsManager = null
    ) {
        $this->counters = $counters ?: Di::_()->get('Votes\Counters');
        $this->indexes = $indexes ?: Di::_()->get('Votes\Indexes');
        $this->acl = $acl ?: ACL::_();
        $this->eventsDispatcher = $eventsDispatcher ?: Di::_()->get('EventsDispatcher');
        $this->friendlyCaptchaManager ??= Di::_()->get('FriendlyCaptcha\Manager');
        $this->experimentsManager ??= new ExperimentsManager();
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Casts a vote
     * @param Vote $vote
     * @param array $options
     * @return bool
     * @throws PuzzleReusedException
     * @throws UnverifiedEmailException
     * @throws InvalidSolutionException
     * @throws PuzzleExpiredException
     * @throws SignatureMismatchException
     * @throws SolutionAlreadySeenException
     * @throws \SodiumException
     */
    public function cast($vote, array $options = [])
    {
        $options = array_merge([
            'events' => true
        ], $options);

        if (!$this->acl->interact($vote->getEntity(), $vote->getActor(), "vote{$vote->getDirection()}")) {
            throw new \Exception('Actor cannot interact with entity');
        }

        $done = $this->eventsDispatcher->trigger('vote:action:cast', $vote->getEntity()->type, [
            'vote' => $vote
        ], null);

        if ($done === null) {
            //update counts
            $this->counters->update($vote);

            //update indexes
            $done = $this->indexes->insert($vote);
        }

        $this->experimentsManager->setUser($this->user);
        $eventOptions = [
            'vote' => $vote
        ];
        if ($vote->getDirection() === "up" && $this->experimentsManager->isOn("minds-3119-captcha-for-engagement")) {
            $isPuzzleValid = false;
            try {
                $isPuzzleValid = $this->friendlyCaptchaManager->verify(
                    $options['puzzleSolution'],
                    DifficultyScalingType::DIFFICULTY_SCALING_VOTE_UP
                );
            } catch (InvalidSolutionException $e) {
            }

            $eventOptions['isFriendlyCaptchaPuzzleValid'] = $isPuzzleValid;
        }

        if ($done && $options['events']) {
            $this->eventsDispatcher->trigger('vote', $vote->getDirection(), $eventOptions);

            if (!$eventOptions['isFriendlyCaptchaPuzzleValid']) {
                throw new InvalidSolutionException();
            }
        }

        return $done;
    }

    /**
     * Cancels a vote
     * @param $vote
     * @param array $options
     * @return bool
     * @throws UnverifiedEmailException
     * @throws StopEventException
     */
    public function cancel($vote, array $options = [])
    {
        $options = array_merge([
            'events' => true
        ], $options);

        $done = $this->eventsDispatcher->trigger('vote:action:cancel', $vote->getEntity()->type, [
            'vote' => $vote
        ], null);

        if ($done === null) {
            //update counts
            $this->counters->update($vote, -1);

            //update indexes
            $done = $this->indexes->remove($vote);
        }

        if ($done && $options['events']) {
            $this->eventsDispatcher->trigger('vote:cancel', $vote->getDirection(), [
                'vote' => $vote
            ]);
        }

        return $done;
    }

    /**
     * Returns a boolean stating if actor voted on the entity
     * @param $vote
     * @return bool
     * @throws StopEventException
     */
    public function has($vote)
    {
        $value = $this->eventsDispatcher->trigger('vote:action:has', $vote->getEntity()->type, [
            'vote' => $vote
        ], null);

        if ($value === null) {
            $value = $this->indexes->exists($vote);
        }

        return $value;
    }

    /**
     * Toggles a vote (cancels if exists, votes if doesn't) [wrapper]
     * @param $vote
     * @param array $options
     * @return bool
     * @throws StopEventException
     * @throws UnverifiedEmailException
     */
    public function toggle($vote, array $options = [])
    {
        $options = array_merge([
            'events' => true
        ], $options);

        if (!$this->has($vote)) {
            return $this->cast($vote, $options);
        } else {
            return $this->cancel($vote, $options);
        }
    }

    /**
     * @return iterable<IterableEntity>
     */
    public function getList(VoteListOpts $opts): iterable
    {
        return $this->indexes->getList($opts);
    }
}
