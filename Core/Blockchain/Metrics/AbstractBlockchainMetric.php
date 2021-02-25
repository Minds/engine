<?php
namespace Minds\Core\Blockchain\Metrics;

use Brick\Math\BigDecimal;
use Minds\Core\Data\ElasticSearch;
use Minds\Core\Di\Di;
use Minds\Core\Config;
use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Blockchain\Token;
use Minds\Core\Blockchain\Services\BlockFinder;
use Minds\Traits\MagicAttributes;

/**
 * @method self setOffchain(BigDecimal $offchain)
 * @method BigDecimal getOffchain()
 * @method self setComparative(AbstractBlockchainMetric $comparative)
 * @method AbstractBlockchainMetric getComparative()
 */
abstract class AbstractBlockchainMetric implements BlockchainMetricInterface
{
    use MagicAttributes;

    /** @var ElasticSearch\Client */
    protected $es;

    /** @var Token  */
    protected $token;

    /** @var BlockFinder */
    protected $blockFinder;

    /** @var Config */
    protected $config;

    /** @var PsrWrapper */
    protected $cache;

    /** @var string */
    protected $format = 'token';

    /** @var int */
    protected $to;

    /** @var int */
    protected $from = 0;

    /** @var int */
    protected $comparativeOffset = 86400;

    /** @var AbstractBlockchainMetric */
    protected $comparative;

    /** @var BigDecimal */
    protected $offchain;

    /** @var BigDecimal */
    protected $onchain;

    public function __construct($es = null, $token = null, $blockFinder = null, $config = null, $cache = null)
    {
        $this->es = $es ?? Di::_()->get('Database\ElasticSearch');
        $this->token = $token ?? Di::_()->get('Blockchain\Token');
        $this->blockFinder = $blockFinder ?? Di::_()->get('Blockchain\Services\BlockFinder');
        $this->config = $config ?? Di::_()->get('Config');
        $this->cache = $cache ?? Di::_()->get('Cache\PsrWrapper');

        $this->setTo(time());
        $this->offchain = $this->onchain = BigDecimal::of(0);
    }

    /**
     * Sets the unix timestamp to stop at
     * @param int $to
     * @return self
     */
    public function setTo(int $to): BlockchainMetricInterface
    {
        $this->to = $to;
        return $this;
    }

    /**
     * Sets the unix timestamp to stop at
     * @param int $from
     * @return self
     */
    public function setFrom(int $from): BlockchainMetricInterface
    {
        $this->from = $from;
        return $this;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->getClassName();
    }

    /**
     * The timestamp of the metric
     * @return int
     */
    public function getTimestamp(): int
    {
        return $this->to;
    }

    /**
     * Public export of metric
     * @return array
     */
    public function export(): array
    {
        $offchain = $this->getOffchain();
        $onchain = $this->getOnchain();
        $total = $offchain->plus($onchain);
        $comparativeOffchain = $this->getComparative()->getOffchain();
        $comparativeOnchain = $this->getComparative()->getOnchain();
        $comparativeTotal = $comparativeOffchain->plus($comparativeOnchain);

        return [
            'id' => $this->getClassName(),
            'offchain' => $offchain,
            'onchain' => $onchain,
            'total' => $total,
            'format' => $this->format,
            'comparative' => [
                'offchain' => $comparativeOffchain,
                'onchain' => $comparativeOnchain,
                'total' => $comparativeTotal,
                'total_diff' => $total->minus($comparativeTotal),
                'increase' => $total->minus($comparativeTotal)->isGreaterThan(0),
            ],
        ];
    }

    /**
     * @return string
     */
    protected function getClassName(): string
    {
        $className = get_called_class();
        return str_replace('Minds\\Core\\Blockchain\\Metrics\\', '', $className);
    }
}
