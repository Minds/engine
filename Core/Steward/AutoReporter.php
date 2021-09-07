<?php
/**
 * Minds AutoReport.
 */

namespace Minds\Core\Steward;

use Minds\Core\Di\Di;
use Minds\Core\Config\Config;
use Minds\Core\Reports;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Reports\Jury\Decision;
use Minds\Traits\MagicAttributes;

class AutoReporter
{
    use MagicAttributes;

    const REPORT_THRESHOLD = 4;

    private $dictionary = [];
    /** @var Minds\Entites\User */
    private $stewardUser;
    /** @var Minds\Core\EntitiesBuilder */
    private $entitiesBuilder;
    /** @var Minds\Core\Config */
    private $config;
    /** @var Minds\Core\Reports\UserReports\Manager */
    private $reportManager;
    /** @var Minds\Core\Reports\Jury\Manager */
    private $juryManager;
    /** @var Minds\Core\Reports\Manager */
    private $moderationManager;

    public function __construct(
        Config $config = null,
        EntitiesBuilder $entitiesBuilder = null,
        Reports\UserReports\Manager $reportManager = null,
        Reports\Jury\Manager $juryManager = null,
        Reports\Manager $moderationManager = null
    ) {
        $this->config = $config ?: Di::_()->get('Config');
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
        $this->reportManager = $reportManager ?: Di::_()->get('Moderation\Reports\Manager');
        $this->juryManager = $juryManager ?: Di::_()->get('Moderation\Jury\Manager');
        $this->moderationManager = $moderationManager ?: Di::_()->get('Moderation\Manager');

        //Fun static mappings begin here. I'm so sorry, world.
        //On the plus side, our developers' greps got a lot more interesting...
        $this->dictionary['adult'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 1);
        $this->dictionary['amateur'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 1);
        $this->dictionary['anal'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 1);
        $this->dictionary['anilingus'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 4);
        $this->dictionary['asian'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 1);
        $this->dictionary['ass'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_NUDITY, 1);
        $this->dictionary['autoerotic'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 2);
        $this->dictionary['babe'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 1);
        $this->dictionary['bangbros'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 4);
        $this->dictionary['banislam'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_RACE, 5);
        $this->dictionary['bareback'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['barenaked'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_NUDITY, 3);
        $this->dictionary['bbw'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 2);
        $this->dictionary['bdsm'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 5);
        $this->dictionary['beastiality'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 5);
        $this->dictionary['beauty'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 1);
        $this->dictionary['bendover'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 1);
        $this->dictionary['bigboobs'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_NUDITY, 1);
        $this->dictionary['bimbos'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 1);
        $this->dictionary['blowjob'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 5);
        $this->dictionary['blumpkin'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 5);
        $this->dictionary['bondage'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 1);
        $this->dictionary['boner'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 1);
        $this->dictionary['boobs'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_NUDITY, 1);
        $this->dictionary['breeding'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 1);
        $this->dictionary['bukkake'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 5);
        $this->dictionary['busty'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_NUDITY, 1);
        $this->dictionary['butt'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_NUDITY, 1);
        $this->dictionary['buttplug'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 1);
        $this->dictionary['camgirls'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 1);
        $this->dictionary['camslut'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 2);
        $this->dictionary['christchurch'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_RACE, 5);
        $this->dictionary['circlejerk'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 1);
        $this->dictionary['clit'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_NUDITY, 1);
        $this->dictionary['cock'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_NUDITY, 5);
        $this->dictionary['cornhole'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['creampie'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['cuck'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['cuckold'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['cum'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['cunnilingus'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['dead'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_VIOLENCE, 3);
        $this->dictionary['deepthroat'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['dfc'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['dick'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_NUDITY, 1);
        $this->dictionary['dildo'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 1);
        $this->dictionary['dom'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['dominatrix'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['ebony'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['ecchi'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['ejaculation'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['erotica'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['faggot'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PROFANITY, 10);
        $this->dictionary['fellatio'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 4);
        $this->dictionary['fet'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['fetish'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['fetlife'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['fingering'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['fisting'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['footfetish'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['gang'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 1);
        $this->dictionary['gangbang'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['gay'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 1);
        $this->dictionary['girls'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 1);
        $this->dictionary['goy'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_RACE, 1);
        $this->dictionary['goyim'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_RACE, 1);
        $this->dictionary['grope'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['hardcore'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['heels'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 1);
        $this->dictionary['hentai'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['hitler'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_RACE, 3);
        $this->dictionary['holocaust'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_RACE, 3);
        $this->dictionary['hot'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 1);
        $this->dictionary['hooker'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 4);
        $this->dictionary['incest'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 4);
        $this->dictionary['intercourse'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['jew'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_RACE, 1);
        $this->dictionary['kike'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_RACE, 10);
        $this->dictionary['kill'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_VIOLENCE, 3);
        $this->dictionary['killing'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_VIOLENCE, 3);
        $this->dictionary['kink'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 1);
        $this->dictionary['kinkster'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['kinky'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['latex'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 1);
        $this->dictionary['lesbian'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 1);
        $this->dictionary['lewd'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['lily'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 1);
        $this->dictionary['lingerie'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 1);
        $this->dictionary['loli'] = new Reason(Reason::REASON_NSFW, Reason::REASON_ILLEGAL_PAEDOPHILIA, 10);
        $this->dictionary['megu'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_RACE, 10);
        $this->dictionary['milf'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['mistress'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['murder'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_VIOLENCE, 1);
        $this->dictionary['muslim'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_RACE, 1);
        $this->dictionary['nazi'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_VIOLENCE, 1);
        $this->dictionary['nigger'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_RACE, 10);
        $this->dictionary['nsfw'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_OTHER, 10);
        $this->dictionary['nude'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_NUDITY, 1);
        $this->dictionary['nudist'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_NUDITY, 1);
        $this->dictionary['nudity'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_NUDITY, 1);
        $this->dictionary['nylon'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 1);
        $this->dictionary['nympho'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['orgasm'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 2);
        $this->dictionary['orgy'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['oppai'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['pantyhose'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 1);
        $this->dictionary['penis'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_NUDITY, 3);
        $this->dictionary['playboy'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_NUDITY, 3);
        $this->dictionary['porn'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 10);
        $this->dictionary['pornstar'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['pussy'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_NUDITY, 1);
        $this->dictionary['racist'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_RACE, 1);
        $this->dictionary['sex'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['sextoy'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['slut'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PROFANITY, 3);
        $this->dictionary['spanking'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['sub'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 1);
        $this->dictionary['submissive'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 1);
        $this->dictionary['thicc'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_NUDITY, 1);
        $this->dictionary['thot'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_NUDITY, 1);
        $this->dictionary['threesome'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 1);
        $this->dictionary['tits'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_NUDITY, 1);
        $this->dictionary['titties'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_NUDITY, 1);
        $this->dictionary['topless'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_NUDITY, 1);
        $this->dictionary['twink'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['unicorn'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 1);
        $this->dictionary['upskirt'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_NUDITY, 3);
        $this->dictionary['vagina'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_NUDITY, 1);
        $this->dictionary['waifu'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['wank'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['webcam'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['whore'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PROFANITY, 5);
        $this->dictionary['woa'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['xxx'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 10);
        $this->dictionary['yiffy'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
        $this->dictionary['zoophilia'] = new Reason(Reason::REASON_NSFW, Reason::REASON_NSFW_PORNOGRAPHY, 3);
    }

    /**
     * Examines the text content of an entity and reports if the post contains problematic words
     * The words are arbitrarily weighted and should be replaced by a cached lookup of statistical analysis.
     */
    public function validate($entity, $time = null)
    {
        $reasons = [];
        $time = $time ?: round(microtime(true) * 1000);
        //Build up a list of reasons to flag unique words in the post
        if (isset($entity['message'])) {
            $this->evaluateText($entity['message'], $reasons);
        }
        //Remove reasons that the user has already tagged
        if (isset($entity['nsfw'])) {
            $this->filterReasonsByNSFWTags($reasons, $entity['nsfw']);
        }
        //If we have reasons, score them and pick the top one that crosses our threshold
        if (count($reasons) > 0) {
            $scorer = new ReasonScorer($reasons);
            $scoredReason = $scorer->score();
            if ($scoredReason && $scoredReason->getWeight() >= AutoReporter::REPORT_THRESHOLD) {
                $this->report($entity, $scoredReason, $time);
                if ($this->config->get('steward_autoconfirm')) {
                    $this->cast($entity, $time);
                }
            }

            return $scoredReason;
        }
    }

    protected function cast($entity, $time)
    {
        $report = $this->moderationManager->getReport($entity->guid);
        $stewardUser = $this->entitiesBuilder->single($this->config->get('steward_guid'));

        $decision = new Decision();
        $decision
            ->setAppeal(null)
            ->setAction('uphold')
            ->setUphold(true)
            ->setReport($report)
            ->setTimestamp($time)
            ->setJurorGuid($stewardUser->getGuid())
            ->setJurorHash($stewardUser->getPhoneNumberHash());

        $this->juryManager->cast($decision);
    }

    protected function report($entity, $reason, $time)
    {
        $stewardUser = $this->entitiesBuilder->single($this->config->get('steward_guid'));
        $report = new Reports\Report();
        $report->setEntityGuid($entity->guid)
            ->setEntityOwnerGuid($entity->getOwnerGuid());

        $autoReport = new Reports\UserReports\UserReport();
        $autoReport
            ->setReport($report)
            ->setReporterGuid($stewardUser->guid)
            ->setReasonCode((int) $reason->getReasonCode())
            ->setSubReasonCode($reason->getSubreasonCode())
            ->setTimestamp($time);
        $this->reportManager->add($autoReport);
    }

    protected function evaluateText($text, &$reasons)
    {
        $words = $this->getUniqueWords($text);
        foreach ($words as $word) {
            $this->evaluateWord($word, $reasons);
        }
    }

    protected function evaluateWord($word, &$reasons)
    {
        if (isset($this->dictionary[$word])) {
            $reasons[] = $this->dictionary[$word];
        }
    }

    protected function filterReasonsByNSFWTags(&$reasons, $NSFWtags)
    {
        $reasons = array_filter($reasons, function ($reason) use ($NSFWtags) {
            if ($reason->getReasonCode() == Reason::REASON_NSFW
            && in_array($reason->getSubreasonCode(), $NSFWtags, false)) {
                return false;
            }

            return true;
        });
    }

    protected function getUniqueWords($text)
    {
        $text = preg_replace('/[^a-z\d ]/i', '', $text);

        return array_unique(explode(' ', strtolower($text)));
    }
}
