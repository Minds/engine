<?php
/**
 * Verify email manager
 */
namespace Minds\Core\Email\Verify;

use Minds\Core\Security\SpamBlocks;

class Manager
{
    /** @var $service */
    private $service;

    /** @var $spamBlocksManager */
    private $spamBlocksManager;

    /** @var $bannedDomains */
    private $bannedDomains = [
        'annomails.com',
        'emailweb.xyz',
        'buydiscountdeal.com',
        'palantirmails.com',
        'vincentralpark.com',
        'clickmail.info',
        'marketlink.info',
        'atnextmail.com',
        'hostguru.top',
        'daymailonline.com',
        'uber-mail.com',
        'mailmetal.com',
        'email-24x7.com',
        'getsimpleemail.com',
        'mailsoul.com',
        'portablemails.com',
        'sdghgfsd.com',
        'sdgdsfhs.com',
        'cemeonline77.com',
        'juispow.com',
        'aquiopc.com',
        'ediob.com',
        'weauo.com',
        'irishmanbox.com',
        'oiesl.com',
        'eaeam.com',
        'hiopsps.com',
        'gdijejds.com',
        'aquiopc.com',
        'serpmails.com',
        'lynleegypsycobs.com.au',
        'casino368.com',
        'ooelt.com',
        'lgoisopc.com',
        'jfisduod.com',
        'rtsfgdso.com',
        'uodib.com',
        'sukkerpappa.com',
        'hoaup.com',
        'oeewc.com',
        'us.yoshisad.com',
        'deepbluez.com',
        'murakamibooks.com',
    ];

    public function __construct($service = null, $spamBlocksManager = null)
    {
        $this->service = $service ?: new Services\TheChecker;
        $this->spamBlocksManager = $spamBlocksManager ?: new SpamBlocks\Manager;
    }

    /**
     * Verify if an email is valid
     * @param string $email
     * @return bool
     */
    public function verify($email)
    {
        $domain = explode('@', strtolower($email))[1];
        if (in_array($domain, $this->bannedDomains, true)) {
            return false;
        }

        $hash = hash('sha256', $email);
        $spamBlock = new SpamBlocks\SpamBlock;
        $spamBlock->setKey('email_hash')
            ->setValue($hash);
        
        if ($this->spamBlocksManager->isSpam($spamBlock)) {
            return false;
        }

        if (!$this->service->verify($email)) {
            $this->spamBlocksManager->add($spamBlock);
            return false;
        }

        return true;
    }
}
