<?php
/**
 */
namespace Minds\Core\SEO\Sitemaps\Resolvers;

use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;
use Minds\Core\Helpdesk\Question\Manager;
use Minds\Core\SEO\Sitemaps\SitemapUrl;

class HelpdeskResolver
{
    /** @var Manager */
    protected $helpdeskQuestionManager;

    /** @var Logger */
    protected $logger;

    public function __construct($helpdeskQuestionManager = null, $logger = null)
    {
        $this->helpdeskQuestionManager = $helpdeskQuestionManager ?? Di::_()->get('Helpdesk\Question\Manager');
        $this->logger = $logger ?? Di::_()->get('Logger');
    }

    public function getUrls(): iterable
    {
        $questions = $this->helpdeskQuestionManager->getAll([ 'limit' => 5000 ]);
        $i = 0;
        foreach ($questions as $question) {
            ++$i;
            $sitemapUrl = new SitemapUrl();
            $sitemapUrl->setLoc("/help/question/{$question->getUuid()}");
            $this->logger->info("$i: {$sitemapUrl->getLoc()}");
            yield $sitemapUrl;
        }
    }
}
