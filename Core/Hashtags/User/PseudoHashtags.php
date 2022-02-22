<?php
/**
 * This class will sync a users hashtags to their psuedo id
 * to allow for analytics to build privacy respecting recommendations
 */
namespace Minds\Core\Hashtags\User;

use Cassandra\Varint;
use Cassandra\Timestamp;
use Minds\Common\Repository\Response;
use Minds\Common\PseudonymousIdentifier;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use Minds\Core\Di\Di;
use Minds\Core\Hashtags\HashtagEntity;

class PseudoHashtags
{
    /** @var Client */
    protected $db;

    /** @var PseudonymousIdentifier */
    protected $pseudoId;

    public function __construct($db = null, $pseudoId = null)
    {
        $this->db = $db ?: Di::_()->get('Database\Cassandra\Cql');
        $this->pseudoId = $pseudoId ?? new PseudonymousIdentifier();
    }

    /**
     * @param HashtagEntity[] $hashtags
     * @return bool
     */
    public function syncTags(array $hashtags): bool
    {
        $cql = "INSERT INTO pseudo_user_hashtags (pseudo_id, hashtag) VALUES (?, ?)";
    
        foreach ($hashtags as $hashtag) {
            try {
                $params = [
                    $this->pseudoId->getId(),
                    (string) $hashtag->getHashtag()
                ];

                $prepared = new Custom();
                $prepared->query($cql, $params);

                $this->db->request($prepared, true);
            } catch (\Exception $e) {
                error_log(static::class . '::add() CQL Exception ' . $e->getMessage());
                return false;
            }
        }

        return true;
    }

    /**
     * @param HashtagEntity[] $hashtags
     * @return bool
     * @throws \Exception
     */
    public function addTags(array $hashtags): bool
    {
        return $this->syncTags($hashtags);
    }

    /**
     * @param HashtagEntity[] $hashtags
     * @return bool
     * @throws \Exception
     */
    public function removeTags(array $hashtags): bool
    {
        $cql = "DELETE FROM pseudo_user_hashtags WHERE pseudo_id = ? AND hashtag = ?";
    
        foreach ($hashtags as $hashtag) {
            try {
                $params = [
                    $this->pseudoId->getId(),
                    (string) $hashtag->getHashtag()
                ];

                $prepared = new Custom();
                $prepared->query($cql, $params);

                $this->db->request($prepared, true);
            } catch (\Exception $e) {
                error_log(static::class . '::add() CQL Exception ' . $e->getMessage());
                return false;
            }
        }

        return true;
    }
}
