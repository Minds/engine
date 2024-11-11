<?php

namespace Spec\Minds\Core\Comments;

use Minds\Core\Comments\Comment;
use Minds\Core\Comments\SearchRepository;
use Minds\Core\Data\ElasticSearch\Client;
use Minds\Core\Data\ElasticSearch\Prepared\Update as PreparedUpdate;
use Minds\Core\Data\ElasticSearch\Prepared\Delete as PreparedDelete;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SearchRepositorySpec extends ObjectBehavior
{
    protected $client;

    public function let(Client $client)
    {
        $this->beConstructedWith($client);
        $this->client = $client;
        $this->beConstructedWith($this->client);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(SearchRepository::class);
    }

    public function it_should_delete()
    {
        $this->client->request(Argument::any())
            ->shouldBeCalled()
            ->willReturn(true);

        $this->delete('guid')->shouldReturn(true);
    }

    public function it_should_return_false(Comment $comment)
    {
        // Comment
        $comment->getGuid()
            ->shouldBeCalled()
            ->willReturn('1000');
        $comment->getEntityGuid()
            ->shouldBeCalled()
            ->willReturn('2000');
        $comment->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn('3000');
        $comment->getBody()
            ->shouldBeCalled()
            ->willReturn('Body');
        $comment->getAttachments()
            ->shouldBeCalled()
            ->willReturn([]);
        $comment->isMature()
            ->shouldBeCalled()
            ->willReturn(false);
        $comment->isEdited()
            ->shouldBeCalled()
            ->willReturn(false);
        $comment->isSpam()
            ->shouldBeCalled()
            ->willReturn(false);
        $comment->isDeleted()
            ->shouldBeCalled()
            ->willReturn(false);
        $comment->isGroupConversation()
            ->shouldBeCalled()
            ->willReturn(false);
        $comment->getAccessId()
            ->shouldBeCalled()
            ->willReturn('1000');

        // PreparedUpdate
        $query = [
            'index' => 'minds-comments',
            'type' => '_doc',
            'id' => '1000',
            'body' => [
                'doc_as_upsert' => true,
                'doc' => [
                    'guid' => '1000',
                    'entity_guid' => '2000',
                    'owner_guid' => '3000',
                    'parent_guid' => -1,
                    'parent_depth' => 0,
                    'body' => 'Body',
                    'attachments' => '[]',
                    'mature' => false,
                    'edited' => false,
                    'spam' => false,
                    'deleted' => false,
                    'enabled' => true,
                    'group_conversation' => false,
                    'access_id' => '1000',
                    'updated_at' => '2019-01-01 00:00:00',
                    '@timestamp' => '2019-01-01 00:00:00'
                ]
            ]
        ];
        $preparedUpdate = new PreparedUpdate();
        $preparedUpdate->query($query);

        $this->client->request($preparedUpdate)
            ->shouldBeCalled()
            ->willThrow(new \Exception());

        $this->add($comment, '2019-01-01 00:00:00', '2019-01-01 00:00:00', null, 0)->shouldReturn(false);
    }

    public function it_should_add(Comment $comment)
    {
        // Comment
        $comment->getGuid()
            ->shouldBeCalled()
            ->willReturn('1000');
        $comment->getEntityGuid()
            ->shouldBeCalled()
            ->willReturn('2000');
        $comment->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn('3000');
        $comment->getBody()
            ->shouldBeCalled()
            ->willReturn('Body');
        $comment->getAttachments()
            ->shouldBeCalled()
            ->willReturn([]);
        $comment->isMature()
            ->shouldBeCalled()
            ->willReturn(false);
        $comment->isEdited()
            ->shouldBeCalled()
            ->willReturn(false);
        $comment->isSpam()
            ->shouldBeCalled()
            ->willReturn(false);
        $comment->isDeleted()
            ->shouldBeCalled()
            ->willReturn(false);
        $comment->isGroupConversation()
            ->shouldBeCalled()
            ->willReturn(false);
        $comment->getAccessId()
            ->shouldBeCalled()
            ->willReturn('1000');

        // PreparedUpdate
        $query = [
            'index' => 'minds-comments',
            'type' => '_doc',
            'id' => '1000',
            'body' => [
                'doc_as_upsert' => true,
                'doc' => [
                    'guid' => '1000',
                    'entity_guid' => '2000',
                    'owner_guid' => '3000',
                    'parent_guid' => -1,
                    'parent_depth' => 0,
                    'body' => 'Body',
                    'attachments' => '[]',
                    'mature' => false,
                    'edited' => false,
                    'spam' => false,
                    'deleted' => false,
                    'enabled' => true,
                    'group_conversation' => false,
                    'access_id' => '1000',
                    'updated_at' => '2019-01-01 00:00:00',
                    '@timestamp' => '2019-01-01 00:00:00'
                ]
            ]
        ];
        $preparedUpdate = new PreparedUpdate();
        $preparedUpdate->query($query);

        $this->client->request($preparedUpdate)
            ->shouldBeCalled()
            ->willReturn(['result' => 'created']);

        $this->add($comment, '2019-01-01 00:00:00', '2019-01-01 00:00:00', null, 0)->shouldReturn(true);
    }

    public function it_should_not_ack_on_exception()
    {
        // PreparedDelete
        $query = [
            'index' => 'minds-comments',
            'type' => '_doc',
            'id' => '1000',
        ];
        $preparedDelete = new PreparedDelete();
        $preparedDelete->query($query);

        $this->client->request($preparedDelete)
            ->shouldBeCalled()
            ->willThrow(new \Exception());

        $this->delete('1000')->shouldReturn(false);
    }

    public function it_should_ack_on_404()
    {
        // PreparedDelete
        $query = [
            'index' => 'minds-comments',
            'type' => '_doc',
            'id' => '1000',
        ];
        $preparedDelete = new PreparedDelete();
        $preparedDelete->query($query);

        $this->client->request($preparedDelete)
            ->shouldBeCalled()
            ->willThrow(new \OpenSearch\Common\Exceptions\Missing404Exception());

        $this->delete('1000')->shouldReturn(true);
    }
}
