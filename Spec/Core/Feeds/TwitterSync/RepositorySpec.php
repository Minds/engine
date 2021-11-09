<?php

namespace Spec\Minds\Core\Feeds\TwitterSync;

use Cassandra\Timestamp;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Feeds\TwitterSync\ConnectedAccount;
use Minds\Core\Feeds\TwitterSync\Repository;
use Minds\Core\Feeds\TwitterSync\TwitterUser;
use Minds\Exceptions\NotFoundException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spec\Minds\Mocks\Cassandra\Rows as CassandraRows;

class RepositorySpec extends ObjectBehavior
{
    protected $client;

    public function let(Client $client)
    {
        $this->beConstructedWith($client);
        $this->client = $client;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_get_account_from_user_guid()
    {
        $this->client->request(Argument::that(function ($request) {
            return true;
        }))
            ->willReturn(new CassandraRows([
                [
                    'user_guid' => '123',
                    'twitter_user_id' => 123,
                    'twitter_username' => 'mark',
                    'twitter_followers_count' => 10,
                    'last_imported_tweet_id' => '',
                    'last_sync_ts' => new Timestamp(null, 0),
                    'discoverable' => true,
                    'connected_timestamp' => new Timestamp(time(), 0),
                ]
            ], ''));
        $connectedAccount = $this->get("123");
        $connectedAccount->getTwitterUser()->getUserId()->shouldBe('123');
    }

    public function it_should_return_false_if_no_account()
    {
        $this->shouldThrow(NotFoundException::class)->duringGet("456");
    }

    public function it_should_add_new_account()
    {
        $twitterUser = new TwitterUser();
        $twitterUser->setUserId(123)
            ->setUsername('mark');

        $connectedAccount = new ConnectedAccount();
        $connectedAccount->setUserGuid('123')
            ->setTwitterUser($twitterUser)
            ->setLastImportedTweetId('0')
            ->setDiscoverable(true);

        $this->client->request(Argument::that(function ($request) {
            // TODO: better checking of the query...
            return true;
        }))
            ->willReturn(true);

        $this->add($connectedAccount)->shouldBe(true);
    }

    public function it_should_delete_account()
    {
        $connectedAccount = new ConnectedAccount();
        $connectedAccount->setUserGuid('123');

        $this->client->request(Argument::that(function ($request) {
            // TODO: better checking of the query...
            return true;
        }))
            ->willReturn(true);

        $this->delete($connectedAccount)->shouldBe(true);
    }
}
