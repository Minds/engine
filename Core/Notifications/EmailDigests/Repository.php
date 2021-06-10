<?php
/**
 * Minds EmailDigests Notifications Repository.
 */

namespace Minds\Core\Notifications\EmailDigests;

use Cassandra\Bigint;
use Minds\Common\Repository\IterableEntity;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use Minds\Core\Data\Cassandra\Scroll;
use Minds\Core\Di\Di;

class Repository
{
    /** @var Client */
    protected $cql;

    /** @var Scroll */
    protected $scroll;

    public function __construct(Client $cql = null, Scroll $scroll = null)
    {
        $this->cql = $cql ?? Di::_()->get('Database\Cassandra\Cql');
        $this->scroll = $scroll ?? Di::_()->get('Database\Cassandra\Cql\Scroll');
    }

    /**
     * @param EmailDigestOpts $opts
     * @return iterable<IterableEntity>
     */
    public function getList(EmailDigestOpts $opts): iterable
    {
        $statement = "SELECT * FROM notification_email_digests
            WHERE year = ?
                AND month = ?
                AND day = ?
                AND frequency = ?";
        $values = [
            (int) date('Y', $opts->getTimestamp()),
            (int) date('n', $opts->getTimestamp()),
            (int) date('j', $opts->getTimestamp()),
            $opts->getFrequency(),
        ];

        $preapred = new Custom();
        $preapred->query($statement, $values);

        foreach ($this->scroll->request($preapred, $pagingToken) as $row) {
            $marker = new EmailDigestMarker();
            $marker->setTimestamp(strtotime("{$row['year']}-{$row['month']}-{$row['day']}"))
                ->setToGuid((string) $row['to_guid'])
                ->setFrequency($row['frequency']);
            yield new IterableEntity($marker, base64_encode($pagingToken));
        }
    }

    /**
     * @param EmailDigestMarker $marker
     * @return bool
     */
    public function add(EmailDigestMarker $marker): bool
    {
        $statement = "INSERT INTO notification_email_digests (year, month, day, frequency, to_guid)
            VALUES (?,?,?,?,?)";
        $values = [
            (int) date('Y', $marker->getTimestamp()),
            (int) date('n', $marker->getTimestamp()),
            (int) date('j', $marker->getTimestamp()),
            $marker->getFrequency(),
            new Bigint($marker->getToGuid()),
        ];

        $preapred = new Custom();
        $preapred->query($statement, $values);

        return (bool) $this->cql->request($preapred);
    }
}
