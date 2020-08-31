<?php

namespace Minds\Core\Email\V2\Campaigns\Marketing\Gift2020_08_27;

use Minds\Core\Email\Campaigns\EmailCampaign;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\Manager;
use Minds\Core\Wire\Wire;
use Minds\Traits\MagicAttributes;
use Minds\Core\Di\Di;
use Minds\Core\Util\BigNumber;
use Minds\Core\Email\V2\Partials\ActionButton\ActionButton;
use Minds\Core\Queue\Client as QueueClient;

class Gift2020_08_27 extends EmailCampaign
{
    use MagicAttributes;
    /** @var Template */
    protected $template;

    /** @var Mailer */
    protected $mailer;

    /** @var Manager */
    protected $manager;

    public function __construct(Template $template = null, Mailer $mailer = null, Manager $manager = null)
    {
        $this->template = $template ?: new Template();
        $this->mailer = $mailer ?: new Mailer();
        $this->manager = $manager ?: Di::_()->get('Email\Manager');

        $this->campaign = 'global';
        $this->topic = 'exclusive_promotions';
    }

    public function build(): Message
    {
        $validatorHash = sha1(get_class($this) . $this->user->getGUID() . Di::_()->get('Config')->get('emails_secret'));
        $tracking = [
            '__e_ct_guid' => $this->user->getGUID(),
            'campaign' => $this->campaign,
            'topic' => $this->topic,
            'validator' => $validatorHash,
            'unixts' => time(),
        ];

        $subject = "You've received a gift";

        $this->template->setTemplate('default.tpl');
        $this->template->setBody('./template.tpl');
        $this->template->toggleMarkdown(true);
        $this->template->set('user', $this->user);
        $this->template->set('username', $this->user->username);
        $this->template->set('email', $this->user->getEmail());
        $this->template->set('guid', $this->user->getGUID());
        $this->template->set('campaign', $this->campaign);
        $this->template->set('topic', $this->topic);
        // $this->template->set('signoff', '');
        $this->template->set('title', "Token Reward");
        $this->template->set('preheader', "You've received a gift of 1 Minds token");
        $this->template->set('tracking', http_build_query($tracking));

        // Balance
        $offChainBalance = Di::_()->get('Blockchain\Wallets\OffChain\Balance');
        $offChainBalance->setUser($this->user);
        $offChainBalanceVal = BigNumber::_($offChainBalance->get())->div(10 ** 18);
        if ($offChainBalanceVal > 0) {
            $views = number_format($offChainBalanceVal->toDouble() * 1000);
            // Send a push
            $title = "Wow! You already have $offChainBalanceVal tokens!";
            $message = "🎉 Log in and boost your content for up to $views views now 🚀";
            QueueClient::build()
                 ->setQueue('Push')
                 ->send([
                    'user_guid' => $this->user->getGuid(),
                    'uri' => 'notification',
                    'title' => $title,
                    'message' => $message,
                ]);
        }

        $actionButton = (new ActionButton())
            ->setLabel('Claim Gift')
            ->setPath('newsfeed/subscriptions?'.http_build_query($tracking));

        $this->template->set('actionButton', $actionButton->build());

        $message = new Message();
        $message->setTo($this->user)
            ->setMessageId(implode(
                '-',
                [$this->user->guid, sha1($this->user->getEmail()), sha1($this->campaign.$this->topic.time())]
            ))
            ->setSubject($subject)
            ->setHtml($this->template);

        return $message;
    }

    public function send(): void
    {
        if ($this->canSend()) {
            $this->mailer->queue($this->build());
        }
    }
}
