<?php
/**
 * Email Repository
 *
 * @package Minds\Core\Email
 */

namespace Minds\Core\Email;

use Cassandra\Type;
use Cassandra\Varint;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use Minds\Core\Di\Di;
use Minds\Core\Email\EmailSubscription;
use Minds\Entities\User;
use Minds\Core\Email\EmailSubscriptionTypes;

class Repository
{
    /**
     * @var Client
     */
    protected $db;

    /**
     * Repository constructor.
     * @param null $db
     */
    public function __construct($db = null)
    {
        $this->db = $db ?: Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * @param array $options
     * @return EmailSubscription[]
     */
    public function getList(array $options = [])
    {
        $options = array_merge([
            'limit' => 10,
            'offset' => '',
            'user_guid' => null,
            'user_guids' => [],
            'campaign' => null,
            'value' => null,
            'topic' => null,
            'campaigns' => [],
            'topics' => [],
            'allowFiltering' => false
        ], $options);

        $template = 'SELECT * FROM email_subscriptions';
        $where = [];
        $values = [];

        if (isset($options['campaign'])) {
            $options['campaigns'] = [ $options['campaign'] ];
        }

        if (isset($options['campaigns']) && $options['campaigns']) {
            $where[] = 'campaign IN ?';
            $values[] = Type::collection(Type::text())->create(...$options['campaigns']);
        }

        if (isset($options['topic'])) {
            $options['topics'] = [ $options['topic'] ];
        }

        if (isset($options['topics']) && $options['topics']) {
            $where[] = 'topic IN ?';
            $values[] = Type::collection(Type::text())->create(...$options['topics']);
        }

        if (isset($options['user_guid'])) {
            $where[] = 'user_guid = ?';
            $values[] = new Varint($options['user_guid']);
        }

        if ($where) {
            $template .= " WHERE " . implode(" AND ", $where);
        }

        if ($options['allowFiltering']) {
            $template .= " ALLOW FILTERING";
        }

        $query = new Custom();
        $query->query($template, $values);

        $query->setOpts([
            'page_size' => (int) $options['limit'],
            'paging_state_token' => $options['offset'],
        ]);

        $notifications = [];
        $token = '';

        try {
            $result = $this->db->request($query);
        } catch (\Exception $e) {
            error_log($e);
        }

        if (isset($result)) {
            foreach ($result as $row) {
                // if row not in campaign - discard it.
                if (!in_array($row['topic'], EmailSubscriptionTypes::TYPES_GROUPINGS[$row['campaign']], true)) {
                    continue;
                }

                $subscription = new EmailSubscription();
                $subscription->setCampaign($row['campaign'])
                    ->setTopic($row['topic'])
                    ->setUserGuid($row['user_guid']->value())
                    ->setValue($row['value']);

                $notifications[$row['user_guid']->value() . "::" . $row['campaign'] . '::' . $row['topic']] = $subscription;
            }

            $token = base64_encode($result->pagingStateToken());
        }

        // Set default values if not found in db
        // Hacky work around
        if ($userGuid = $options['user_guid']) {
            foreach (EmailSubscriptionTypes::TYPES_GROUPINGS as $campaign => $topics) {
                foreach ($topics as $topic) {
                    $key = "$userGuid::$campaign::$topic";

                    if (!isset($notifications[$key])) {
                        $notifications[$key] = (new EmailSubscription())
                            ->setCampaign($campaign)
                            ->setTopic($topic)
                            ->setUserGuid($userGuid)
                            ->setValue("1");
                    }
                }
            }
        }

        return [
            'data' => array_values($notifications),
            'next' => $token
        ];
    }

    /**
     * @param EmailSubscription $subscription
     * @return bool
     * @throws \Exception
     */
    public function add($subscription)
    {
        if (!$subscription->getUserGuid()) {
            throw new \Exception('user_guid is required');
        }
        if (!$subscription->getCampaign()) {
            throw new \Exception('campaign is required');
        }
        if (!$subscription->getTopic()) {
            throw new \Exception('topic is required');
        }

        $template = "INSERT INTO email_subscriptions (topic, campaign, user_guid, value) VALUES (?, ?, ?, ?)";

        $values = [
            (string) $subscription->getTopic(),
            (string) $subscription->getCampaign(),
            new Varint($subscription->getUserGuid()),
            (string) $subscription->getValue()
        ];

        $query = new Custom();
        $query->query($template, $values);

        return $this->db->request($query);
    }

    /**
     * @param EmailSubscription $subscription
     * @return bool
     * @throws \Exception
     */
    public function delete($subscription)
    {
        if (!$subscription->getUserGuid()) {
            throw new \Exception('user_guid is required');
        }
        if (!$subscription->getCampaign()) {
            throw new \Exception('campaign is required');
        }
        if (!$subscription->getTopic()) {
            throw new \Exception('topic is required');
        }

        $template = "DELETE FROM email_subscriptions where campaign = ? and topic = ? and user_guid = ?";

        $values = [
            (string) $subscription->getCampaign(),
            (string) $subscription->getTopic(),
            new Varint($subscription->getUserGuid())
        ];

        $query = new Custom();
        $query->query($template, $values);

        return $this->db->request($query);
    }
}
