<?php

namespace Minds\Core\Email\V2\Campaigns\Recurring\WireSent;

use Minds\Core\Email\Campaigns\EmailCampaign;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\Manager;
use Minds\Core\Wire\Wire;
use Minds\Core\Wire\SupportTiers;
use Minds\Core\Pro;
use Minds\Traits\MagicAttributes;
use Minds\Core\Di\Di;
use Minds\Core\Util\BigNumber;
use Minds\Core\Email\V2\Partials\ProHeader\ProHeader;

class WireSent extends EmailCampaign
{
    // TODO code docs
    use MagicAttributes;

    /** @var Template */
    protected $template;

    /** @var Mailer */
    protected $mailer;

    /** @var Manager */
    protected $manager;

    /** @var SupportTiers\Manager */
    protected $supportTiersManager;

    /** @var Pro\Manager */
    protected $proManager;

    /* @var Wire */
    protected $wire;

    public function __construct(
        Template $template = null,
        Mailer $mailer = null,
        Manager $manager = null,
        SupportTiers\Manager $supportTiersManager = null,
        Pro\Manager $proManager = null
    ) {
        $this->template = $template ?: new Template();
        $this->mailer = $mailer ?: new Mailer();
        $this->manager = $manager ?: Di::_()->get('Email\Manager');
        $this->supportTiersManager = $supportTiersManager ?? Di::_()->get('Wire\SupportTiers\Manager');
        $this->proManager = $proManager ?? Di::_()->get('Pro\Manager');

        $this->campaign = 'when';
        $this->topic = 'wire_received';
    }

    public function build()
    {
        /** @var SupportTiers\SupportTier */
        $supportTier = $this->supportTiersManager->getByWire($this->wire);
        
        $tracking = [
            '__e_ct_guid' => $this->user->getGUID(),
            'campaign' => $this->campaign,
            'topic' => $this->topic,
        ];

        $this->template->setLocale($this->user->getLanguage());
        $translator = $this->template->getTranslator();

        $subject = $supportTier ? 'Thank you for joining ' .  $this->wire->getReceiver()->get('name') : $translator->trans('Payment sent');

        $timestamp = gettype($this->wire->getTimestamp()) === 'object' ? $this->wire->getTimestamp()->time() : $this->wire->getTimestamp();

        $contract = $this->wire->getMethod() === 'onchain' ? 'wire' : 'offchain:wire';

        $this->template->setTemplate('default.tpl');
        if ($supportTier) {
            $this->template->setBody('./support-tier.tpl');
        } else {
            $this->template->setBody('./template.tpl');
        }
        $this->template->set('user', $this->user);
        $this->template->set('username', $this->user->username);
        $this->template->set('email', $this->user->getEmail());
        $this->template->set('guid', $this->user->getGUID());
        $this->template->set('timestamp', $timestamp);
        $this->template->set('amount', $this->getAmountString($this->wire));
        $this->template->set('receiver', $this->wire->getReceiver());
        $this->template->set('sender', $this->wire->getSender());
        $this->template->set('contract', $contract);
        $this->template->set('campaign', $this->campaign);
        $this->template->set('topic', $this->topic);
        $this->template->set('signoff', $translator->trans('Thank you,'));
        if (!$supportTier) {
            $this->template->set('title', $subject);
        }
        $this->template->set('preheader', $translator->trans("You've sent a payment on Minds."));
        $this->template->set('tracking', http_build_query($tracking));
        $this->template->set('supportTier', $supportTier);

        /** @var Pro\Settings */
        $proSettings = $this->proManager->setUser($this->wire->getReceiver())->get();
        if ($proSettings && $this->proManager->isActive()) {
            $proHeader = (new ProHeader())
                ->set('tracking', $trackingQuery)
                ->setProSettings($proSettings);
            $this->template->set('custom_header', $proHeader->build());
        }

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

    public function send()
    {
        if ($this->canSend()) {
            $this->mailer->send($this->build());
        }
    }

    private function getAmountString(Wire $wire) : string
    {
        $amount = $wire->getAmount();
        if ($wire->getMethod() === 'tokens') {
            $amount = BigNumber::fromPlain($wire->getAmount(), 18)->toDouble();
            $currency = $amount === 1 ? 'token' : 'tokens';
        } else {
            $currency = strtoupper($wire->getMethod());
        }

        if ($wire->getMethod() === 'usd') {
            $amount = $amount / 100;
        }

        return "$amount $currency";
    }
}
