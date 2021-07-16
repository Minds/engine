<?php
namespace Minds\Core\Analytics\EntityCentric;

use Minds\Core\Analytics\Views\Repository as ViewsRepository;
use DateTime;
use Exception;

class ViewsSynchroniser
{
    /** @var array */
    private $records = [];

    /** @var ViewsRepository */
    private $viewsRepository;

    /** @var int */
    protected $from;

    public function __construct($viewsRepository = null)
    {
        $this->viewsRepository = $viewsRepository ?: new ViewsRepository();
    }

    /**
     * @param int $from
     * @return self
     */
    public function setFrom($from): self
    {
        $this->from = $from;
        return $this;
    }

    /**
     * Convert to records
     * @return iterable
     */
    public function toRecords(): iterable
    {
        $date = (new DateTime())->setTimestamp($this->from);

        $opts['day'] = intval($date->format('d'));
        $opts['month'] = intval($date->format('m'));
        $opts['year'] = $date->format('Y');

        $opts['from'] = $this->from;

        $i = 0;
        while (true) {
            $result = $this->viewsRepository->getList($opts);

            $opts['offset'] = $result->getPagingToken();

            foreach ($result as $view) {
                // if (!in_array($view->getSource(), [ 'single', 'feed/channel'])) {
                //     continue;
                // }
                $this->downsampleViewToRecord($view);
                error_log(++$i);
            }

            if ($result->isLastPage()) {
                break;
            }
        }

        foreach ($this->records as $record) {
            yield $record;
        }
    }

    /**
     * Add entity to map
     * @param View $view
     * @return void
     */
    private function downsampleViewToRecord($view): void
    {
        $entityUrn = $view->getEntityUrn();

        if (!isset($this->records[$view->getEntityUrn()])) {
            $timestamp = (new \DateTime())->setTimestamp($view->getTimestamp())->setTime(0, 0, 0);
            $record = new EntityCentricRecord();
            $record->setEntityUrn($view->getEntityUrn())
                ->setOwnerGuid($view->getOwnerGuid())
                ->setTimestamp($timestamp->getTimestamp())
                ->setResolution('day');

            $this->records[$view->getEntityUrn()] = $record;
        }
        if ($view->getCampaign()) {
            $this->records[$view->getEntityUrn()]->incrementSum('views::boosted');
        } else {
            $this->records[$view->getEntityUrn()]->incrementSum('views::organic');
        }

        if ($view->getSource() === 'single') {
            $this->records[$view->getEntityUrn()]->incrementSum('views::single');
        }

        $this->records[$view->getEntityUrn()]->incrementSum('views::total');
    }
}
