<?php

namespace Minds\Controllers\Cli\Migrations;

use Minds\Core;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Di\Di;
use Minds\Cli;
use Minds\Core\Email\SendGrid\Lists\MonetizedUsersList;
use Minds\Interfaces;
use Minds\Entities\User;

class TokenCustomers extends Cli\Controller implements Interfaces\CliControllerInterface
{
    /** @var Core\Wire\Repository();  */
    private $wireRepository;

    /** @var Core\Pro\Manager */
    private $proManager;

    /** @var MonetizedUsersList */
    private $monetizedUsersList;

    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    public function __construct()
    {
        $this->wireRepository = Di::_()->get('Wire\Repository');
        $this->proManager = Di::_()->get('Pro\Manager');
        $this->monetizedUsersList = new MonetizedUsersList();
        $this->entitiesBuilder = Di::_()->get('EntitiesBuilder');
    }

    public function help($command = null)
    {
        $this->out('Syntax usage: cli migrations reports');
    }

    /**
     * Intended to be run by someone who knows what they are doing...
     * This scans through entire current and historical wires for plus/pro and applies the payment methods to the user
     */
    public function exec()
    {
        Core\Security\ACL::$ignore = true;
 
        $i = 0;
        foreach ($this->monetizedUsersList->getContacts() as $contact) {
            ++$i;
            /** @var User */
            $user = $this->entitiesBuilder->single($contact->getUserGuid());

            if (!$user) {
                $this->out("$i: " . $contact->getUserGuid() . ' not found :/');
                continue;
            }

            $proExpires = $contact->getProExpires();
            $plusExpires = $contact->getPlusExpires();


            if (!$proExpires && !$plusExpires) {
                continue;
            }

            $outStr = "$i: {$user->getGuid()}";

            $lastPayments = [
                'plus' => [
                    'method' => 'tokens',
                    'unixTs' => 0,
                ],
                'pro' => [
                    'method' => 'tokens',
                    'unixTs' => 0,
                ]
            ];

            foreach ([ 'usd', 'tokens' ] as $paymentMethod) {
                foreach ([ '730071191229833224' => 'plus', '1030390936930099216' => 'pro' ] as $productGuid => $productName) {
                    $list = $this->wireRepository->getList([
                        'receiver_guid' => $productGuid,
                        'sender_guid' => $user->getGuid(),
                        'allowFiltering' => true,
                        'method' => $paymentMethod,
                    ]);

                    foreach ($list['wires'] as $wire) {
                        $unixTs = $wire->getTimestamp()->time();
                        if ($lastPayments[$productName]['unixTs'] < (int) $unixTs) {
                            $lastPayments[$productName] = [
                                'unixTs' => $unixTs,
                                'method' => $paymentMethod,
                            ];
                        }
                    }
                }
            }

            $outStr .= " plus: {$lastPayments['plus']['method']} pro: {$lastPayments['pro']['method']}";

            if ($user->isPro() && $lastPayments['pro']['method'] === 'tokens' ||
                (!$user->isPro() && $lastPayments['plus']['method'] === 'tokens')
            ) {
                $this->proManager->setUser($user)->set([
                    'payout_method' => 'tokens',
                ]);
                $outStr .= " || Updating payout_method ";
            }

            if ($user->getProMethod() !== $lastPayments['pro']['method']
                || $user->getPlusMethod() !== $lastPayments['plus']['method']) {
                $user->setProMethod($lastPayments['pro']['method']);
                $user->setPlusMethod($lastPayments['plus']['method']);
                $user->save();
                $outStr .= " || Out of sync || ";
            }

            $this->out($outStr);
        }
    }
}
