<?php
namespace Minds\Core\Notifications\Push\Settings;

use Cassandra\Bigint;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use Minds\Core\Di\Di;

class Repository
{
    /** @var Client */
    protected $cql;

    public function __construct(Client $cql = null)
    {
        $this->cql = $cql ?? Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * @param PushSettingListOpts $opts
     * @return iterable<PushSetting>
     */
    public function getList(SettingsListOpts $opts): iterable
    {
        $statement = "SELECT * FROM push_notifications_settings WHERE user_guid = ?";
        $values = [ new Bigint($opts->getUserGuid())];

        $prepared = new Custom();
        $prepared->query($statement, $values);

        $rows = $this->cql->request($prepared);

        foreach ($rows as $row) {
            $pushSetting = new PushSetting();
            $pushSetting
                ->setUserGuid((string) $row['user_guid'])
                ->setNotificationGroup($row['notification_group'])
                ->setEnabled($row['enabled']);
            yield $pushSetting;
        }
    }

    /**
     * @param PushSetting $pushSetting
     * @return bool
     */
    public function add(PushSetting $pushSetting): bool
    {
        $statement = "INSERT INTO push_notifications_settings
            (user_guid, notification_group, enabled)
            VALUES (?, ?, ?)";
        
        $values = [
            new Bigint($pushSetting->getUserGuid()),
            $pushSetting->getNotificationGroup(),
            $pushSetting->getEnabled(),
        ];

        $prepared = new Custom();
        $prepared->query($statement, $values);

        return !!$this->cql->request($prepared);
    }
}
