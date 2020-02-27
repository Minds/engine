<?php

namespace Minds\Core\Email\Batches;

use Minds\Core\Email\Campaigns;
use Minds\Core\Email\EmailSubscribersIterator;
use Minds\Traits\MagicAttributes;

class InactivePrompt implements EmailBatchInterface
{
    use MagicAttributes;

    /** @var string */
    protected $offset;

    /** @var string */
    protected $templateKey;

    /** @var string */
    protected $subject;

    /**
     * @param string $offset
     *
     * @return Promotion
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    public function setDryRun($bool)
    {
        return $this;
    }

    /**
     * @param string $templateKey
     *
     * @return Promotion
     */
    public function setTemplateKey($templateKey)
    {
        $this->templateKey = $templateKey;

        return $this;
    }

    /**
     * @param string $subject
     *
     * @return Promotion
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function run()
    {
        $iterator = new EmailSubscribersIterator();
        $iterator->setCampaign('with')
            ->setTopic('posts_missed_since_login')
            ->setValue(true)
            ->setOffset($this->offset);

        $i = 0;
        foreach ($iterator as $user) {
            // $user = new \Minds\Entities\User('mark');
            // $user->bounced = false;

            if ($user->kite_ref_ts && $user->kite_ref_ts > strtotime('28 days ago')) {
                continue;
            }

            if ($user->bounced) {
                echo "\n[$i]: $user->guid ($iterator->offset) bounced";
                continue;
            }
            ++$i;

            $campaign = new Campaigns\InactivePrompt();

            $campaign
                ->setUser($user)
                ->send();

            echo "\n[$i]: $user->guid ($iterator->offset)";
            // exit;
        }
    }
}
