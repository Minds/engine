<?php
/**
 * Repository.
 *
 * @author edgebal
 */

namespace Minds\Core\Pro;

use Cassandra\Bigint;
use Cassandra\Rows;
use Exception;
use Minds\Common\Repository\Response;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use Minds\Core\Di\Di;

class Repository
{
    /** @var Client */
    protected $db;

    /**
     * Repository constructor.
     *
     * @param Client $db
     */
    public function __construct(
        $db = null
    ) {
        $this->db = $db ?: Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * @param array $opts
     *
     * @return Response
     */
    public function getList(array $opts = []): Response
    {
        $opts = array_merge([
            'user_guid' => null,
            'domain' => null,
            'limit' => null,
            'offset' => null,
        ], $opts);

        $cql = 'SELECT * FROM pro';
        $where = [];
        $values = [];
        $cqlOpts = [];

        if ($opts['user_guid']) {
            $where[] = 'user_guid = ?';
            $values[] = new Bigint($opts['user_guid']);
        } elseif ($opts['domain']) {
            $cql = 'SELECT * FROM pro_by_domain';
            $where[] = 'domain = ?';
            $values[] = $opts['domain'];
        }

        if ($where) {
            $cql .= sprintf(' WHERE %s', implode(' AND ', $where));
        }

        if ($opts['limit']) {
            $cqlOpts['page_size'] = (int) $opts['limit'];
        }

        if ($opts['offset']) {
            $cqlOpts['paging_state_token'] = base64_decode($opts['offset'], true);
        }

        $prepared = new Custom();
        $prepared->query($cql, $values);
        $prepared->setOpts($cqlOpts);

        $response = new Response();

        try {
            /** @var Rows $rows */
            $rows = $this->db->request($prepared);

            if ($rows) {
                foreach ($rows as $row) {
                    $settings = new Settings();
                    $settings
                        ->setUserGuid($row['user_guid']->toInt())
                        ->setDomain($row['domain']);

                    $data = json_decode($row['json_data'] ?: '{}', true);
                    $settings
                        ->setTitle($data['title'] ?? '')
                        ->setHeadline($data['headline'] ?? '')
                        ->setTextColor($data['text_color'] ?? '')
                        ->setPrimaryColor($data['primary_color'] ?? '')
                        ->setPlainBackgroundColor($data['plain_background_color'] ?? '')
                        ->setTileRatio($data['tile_ratio'] ?? '')
                        ->setFooterText($data['footer_text'] ?? '')
                        ->setFooterLinks($data['footer_links'] ?? [])
                        ->setTagList($data['tag_list'] ?? [])
                        ->setScheme($data['scheme'] ?? '')
                        ->setCustomHead($data['custom_head'] ?? '')
                        ->setHasCustomLogo($data['has_custom_logo'] ?? false)
                        ->setHasCustomBackground($data['has_custom_background'] ?? false)
                        ->setSplash($data['splash'] ?? false)
                        ->setTimeUpdated($data['time_updated'] ?? 0)
                        ->setPayoutMethod($data['payout_method'] ?? 'usd')
                    ;

                    $response[] = $settings;
                }

                $response
                    ->setLastPage($rows->isLastPage())
                    ->setPagingToken(base64_encode($rows->pagingStateToken()));
            }
        } catch (Exception $e) {
            error_log("[ProRepository] $e");
            $response->setException($e);
        }

        return $response;
    }

    /**
     * @param Settings $settings
     *
     * @return bool
     *
     * @throws Exception
     */
    public function add(Settings $settings): bool
    {
        if (!$settings->getUserGuid()) {
            throw new Exception('Invalid user GUID');
        }

        $cql = 'INSERT INTO pro (user_guid, domain, json_data) VALUES (?, ?, ?)';
        $settings = [
            new Bigint($settings->getUserGuid()),
            $settings->getDomain(),
            json_encode([
                'user_guid' => (string) $settings->getUserGuid(),
                'domain' => $settings->getDomain(),
                'title' => $settings->getTitle(),
                'headline' => $settings->getHeadline(),
                'text_color' => $settings->getTextColor(),
                'primary_color' => $settings->getPrimaryColor(),
                'plain_background_color' => $settings->getPlainBackgroundColor(),
                'tile_ratio' => $settings->getTileRatio(),
                'footer_text' => $settings->getFooterText(),
                'footer_links' => $settings->getFooterLinks(),
                'tag_list' => $settings->getTagList(),
                'scheme' => $settings->getScheme(),
                'custom_head' => $settings->getCustomHead(),
                'has_custom_logo' => $settings->hasCustomLogo(),
                'has_custom_background' => $settings->hasCustomBackground(),
                'splash' => $settings->getSplash(),
                'time_updated' => $settings->getTimeUpdated(),
                'payout_method' => $settings->getPayoutMethod(),
            ]),
        ];

        $prepared = new Custom();
        $prepared->query($cql, $settings);

        return (bool) $this->db->request($prepared, true);
    }

    /**
     * @param Settings $settings
     *
     * @return bool
     *
     * @throws Exception
     */
    public function update(Settings $settings): bool
    {
        return $this->add($settings);
    }

    /**
     * @param Settings $settingsRef
     *
     * @return bool
     *
     * @throws Exception
     */
    public function delete(Settings $settingsRef): bool
    {
        if (!$settingsRef->getUserGuid()) {
            throw new Exception('Invalid user GUID');
        }

        $cql = 'DELETE FROM pro WHERE user_guid = ?';
        $settingsRef = [
            new Bigint($settingsRef->getUserGuid()),
        ];

        $prepared = new Custom();
        $prepared->query($cql, $settingsRef);

        return (bool) $this->db->request($prepared, true);
    }
}
