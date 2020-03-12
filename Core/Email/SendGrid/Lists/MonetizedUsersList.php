<?php
namespace Minds\Core\Email\SendGrid\Lists;

use Minds\Core\Di\Di;
use Minds\Core\Data\ElasticSearch\Prepared;
use Minds\Core\Email\SendGrid\SendGridContact;

class MonetizedUsersList implements SendGridListInterface
{
    /** @var Scroll */
    protected $scroll;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    public function __construct($scroll = null, $entitiesBuilder = null)
    {
        $this->scroll = $scroll ?? Di::_()->get('Database\ElasticSearch\Scroll');
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
    }

    /**
     * @return SendGridContact[]
     */
    public function getContacts(): iterable
    {
        $prepared = new Prepared\Search();
        $prepared->query([
            'index' => 'minds_badger',
            'type' => 'user',
            'body' => [
                'query' => [
                    'bool' => [
                        'should' => [
                            [
                                'exists' => [
                                     'field' => 'merchant',
                                ],
                            ],
                            [
                                'exists' => [
                                    'field' => 'pro_expires',
                                ],
                            ],
                            [
                                'exists' => [
                                    'field' => 'plus_expires',
                                ]
                            ],
                        ]
                    ]
                ],
            ],
        ]);

        foreach ($this->scroll->request($prepared) as $doc) {
            $user = $this->entitiesBuilder->single($doc['_id']);
            if (!$user) {
                continue;
            }
            $contact = new SendGridContact();
            $contact
                ->setUserGuid($user->getGuid())
                ->setUsername($user->get('username'))
                ->setEmail($user->getEmail())
                ->setProExpires($user->getProExpires())
                ->setPlusExpires($user->get('plus_expires'))
                ->setIsMerchant($user->getMerchant() && $user->getMerchant()['service'] === 'stripe');

            if (!$contact->getEmail()) {
                continue;
            }
            yield $contact;
        }
    }
}
