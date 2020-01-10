<?php
namespace Minds\Core\Payments\Plans;

use Minds\Core;
use Minds\Core\Di\Di;

class Repository
{
    private $db;
    private $config;

    private $entity_guid;
    private $user_guid;

    public function __construct($db = null, $config = null)
    {
        $this->db = $db ?: Di::_()->get('Database\Cassandra\Cql');
        $this->config = $config ?: Di::_()->get('Config');
    }

    public function setEntityGuid($guid)
    {
        $this->entity_guid = $guid;
        return $this;
    }

    public function setUserGuid($guid)
    {
        $this->user_guid = $guid;
        return $this;
    }

    /**
     * Return all subscriptions for an entity
     */
    public function getAll(array $opts = [])
    {
        $opts = array_merge([
          'limit' => 10,
          'offset' => ''
        ], $opts);

        $query = new Core\Data\Cassandra\Prepared\Custom();
        $query->query("SELECT * FROM plans WHERE entity_guid = ?", [
            (string) $this->entity_guid
          ]);
        try {
            $result = $this->db->request($query);
            $guids = [];
            foreach ($result as $row) {
                $guids[] = $row['guid'];
            }
            return $guids;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Return all entities a user is subscribed to
     */
    public function getAllSubscriptions($plansIds = [''], array $opts = [])
    {
        $opts = array_merge([
            'limit' => 10,
            'offset' => ''
        ], $opts);

        $results = [];

        $query = new Core\Data\Cassandra\Prepared\Custom();

        if ($planIds) {
            $query->query("SELECT * FROM plans_by_user_guid WHERE plan IN ? AND user_guid = ? ", [
                \Cassandra\Type::collection(\Cassandra\Type::text())->create(... $plansIds),
                (string) $this->user_guid
            ]);
        } else {
            $query->query("SELECT * FROM plans_by_user_guid WHERE user_guid = ?", [
                (string) $this->user_guid
            ]);
        }

        try {
            $result = $this->db->request($query);
            foreach ($result as $row) {
                $results[] = $plan = new Plan();
                $plan->setEntityGuid($row['entity_guid'])
                  ->setName($row['plan'])
                  ->setUserGuid($row['user_guid'])
                  ->setStatus($row['status'])
                  ->setExpires($row['expires'])
                  ->setAmount($row['amount'])
                  ->setSubscriptionId($row['subscription_id']);
            }
        } catch (\Exception $e) {
            return [];
        }

        return $results;
    }

    /**
     * Return a subscription to an entity
     * @return Plan
     */
    public function getSubscription($planId)
    {
        $plan = new Plan();
        $query = new Core\Data\Cassandra\Prepared\Custom();
        $query->query("SELECT * FROM plans
          WHERE entity_guid = ? AND plan = ? AND user_guid = ?", [
            (string) $this->entity_guid,
            (string) $planId,
            (string) $this->user_guid
          ]);
        try {
            $result = $this->db->request($query);
            $plan->setEntityGuid($result[0]['entity_guid'])
              ->setName($result[0]['plan'])
              ->setUserGuid($result[0]['user_guid'])
              ->setStatus($result[0]['status'])
              ->setExpires($result[0]['expires'])
              ->setAmount($result[0]['amount'])
              ->setSubscriptionId($result[0]['subscription_id']);
        } catch (\Exception $e) {
        }

        return $plan;
    }

    /**
     * Return a subscription to an entity
     * @return Plan
     */
    public function getSubscriptionById($id)
    {
        $plan = new Plan();
        $query = new Core\Data\Cassandra\Prepared\Custom();
        $query->query("SELECT * FROM plans_by_subscription_id WHERE subscription_id = ?", [
            (string) $id
          ]);
        try {
            $result = $this->db->request($query);
            $plan->setEntityGuid($result[0]['entity_guid'])
              ->setName($result[0]['plan'])
              ->setUserGuid($result[0]['user_guid'])
              ->setStatus($result[0]['status'])
              ->setExpires($result[0]['expires'])
              ->setAmount($result[0]['amount'])
              ->setSubscriptionId($result[0]['subscription_id']);
        } catch (\Exception $e) {
        }

        return $plan;
    }

    /**
     * Return all users subscribed to an entity
     */
    public function getAllSubscribers($planId = '', array $opts = [])
    {
        $opts = array_merge([
          'limit' => 10,
          'offset' => ''
        ], $opts);

        $query = new Core\Data\Cassandra\Prepared\Custom();
        $query->query("SELECT * FROM plans WHERE plan = ? AND entity_guid = ? ALLOW FILTERING", [
            (string) $planId,
            (string) $this->entity_guid
        ]);
        try {
            $result = $this->db->request($query);
            $guids = [];
            foreach ($result as $row) {
                $guids[] = $row['user_guid'];
            }
            return $guids;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Return all users subscribed to an entity
     */
    public function getSubscriberCount($planId = 'exclusive', array $opts = [])
    {
        $opts = array_merge([], $opts);

        $query = new Core\Data\Cassandra\Prepared\Custom();
        $query->query("SELECT count(*) FROM plans WHERE plan = ? AND entity_guid = ? ALLOW FILTERING", [
            (string) $planId,
            (string) $this->entity_guid
        ]);
        try {
            $result = $this->db->request($query);
            return (int) $result[0]['count'];
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function add($plan)
    {
        $query = new Core\Data\Cassandra\Prepared\Custom();
        $query->query(
            "INSERT INTO plans
          (entity_guid, plan, user_guid, status, subscription_id, expires)
          VALUES (?, ?, ?, ?, ?, ?)",
            [
            (string) $plan->getEntityGuid(),
            (string) $plan->getName(),
            (string) $plan->getUserGuid(),
            (string) $plan->getStatus(),
            (string) $plan->getSubscriptionId(),
            (int) $plan->getExpires(),
          ]
        );
        try {
            $result = $this->db->request($query);
        } catch (\Exception $e) {
        }
        return $this;
    }

    public function cancel($plan)
    {
        if (is_string($plan)) {
            $plan = new Plan();
            $plan->setName($plan)
              ->setEntityGuid($this->entity_guid)
              ->setUserGuid($this->user_guid);
        }

        $query = new Core\Data\Cassandra\Prepared\Custom();

        $query->query("DELETE FROM plans WHERE entity_guid = ? AND plan = ? AND user_guid = ?", [
            (string) $plan->getEntityGuid(),
            (string) $plan->getName(),
            (string) $plan->getUserGuid()
        ]);

        try {
            $result = $this->db->request($query);
        } catch (\Exception $e) {
        }

        return $this;
    }
}
