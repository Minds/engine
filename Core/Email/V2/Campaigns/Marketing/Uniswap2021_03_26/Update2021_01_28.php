<?php

namespace Minds\Core\Email\V2\Campaigns\Marketing\Uniswap2021_03_26;

use Minds\Core\Email\Campaigns\EmailCampaign;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\Manager;
use Minds\Core\Wire\Wire;
use Minds\Traits\MagicAttributes;
use Minds\Core\Di\Di;
use Minds\Core\Email\V2\Partials\ActionButton\ActionButton;

class Uniswap2021_03_26 extends EmailCampaign
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
        $this->topic = 'minds_news';
    }

    public function build(): Message
    {
        $tracking = [
            '__e_ct_guid' => $this->user->getGUID(),
            'campaign' => $this->campaign,
            'topic' => $this->topic,
        ];

        $subject = "The MINDS token is now on Uniswap";

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
        $this->template->set('preheader', "Please take a minute to read our blog about our recent integration with Uniswap, a decentralized trading protocol.");
        $this->template->set('tracking', http_build_query($tracking));

        $actionButton = (new ActionButton())
            ->setLabel('Read our blog')
            ->setPath('minds/blog/minds-is-now-listed-on-uniswap-here-s-what-it-means-1221912862054379520?'.http_build_query($tracking));

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
            $this->mailer->send($this->build());
        }
    }
}
