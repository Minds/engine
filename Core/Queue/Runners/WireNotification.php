<?php
/**
 * @author Marcelo
 */

namespace Minds\Core\Queue\Runners;

use Minds\Core\Events\Dispatcher;
use Minds\Core\Queue;
use Minds\Core\Queue\Interfaces;
use Minds\Core\Util\BigNumber;
use Minds\Core\Wire;
use Minds\Entities;

class WireNotification implements Interfaces\QueueRunner
{
    public function run()
    {
        $client = Queue\Client::Build();
        $client->setQueue('WireNotification')
            ->receive(function ($data) {
                echo 'Received a wire notification request\n';
                $data = $data->getData();

                $wire = isset($data['wire']) ? unserialize($data['wire']) : null;
                $entity = isset($data['entity']) ? unserialize($data['entity']) : null;

                if (is_numeric($entity)) {
                    $entity = Entities\Factory::build($entity);
                }

                if (!$entity || !is_object($entity)) {
                    return;
                }

                $receiverUser = $entity->type === 'user' ? $entity : $entity->getOwnerEntity();

                if (isset($data['walletNotSetupException']) && $data['walletNotSetupException']) {
                    $message = 'Somebody wanted to send you a Tokens wire, but you need to setup your wallet address first! You can set it up in your Wallet.';
                    Dispatcher::trigger('notification', 'wire', [
                        'to' => [$receiverUser->getGUID()],
                        'from' => 100000000000000519,
                        'notification_view' => 'custom_message',
                        'params' => ['message' => $message],
                        'message' => $message,
                    ]);
                } else {
                    if (!$wire || !is_object($wire)) {
                        return;
                    }

                    $senderUser = $wire->getSender();

                    //send notification to receiver
                    Dispatcher::trigger('notification', 'wire', [
                        'to' => [$receiverUser->guid],
                        'from' => $senderUser->guid,
                        'notification_view' => 'wire_happened',
                        'params' => [
                            'amount' => $this->getAmountString($wire),
                            'from_guid' => $senderUser->guid,
                            'from_username' => $senderUser->username,
                            'to_guid' => $receiverUser->guid,
                            'to_username' => $receiverUser->username,
                            'subscribed' => $data['subscribed'],
                        ],
                    ]);

                    // send wire email to receiver
                    Dispatcher::trigger('wire:email', 'wire', [
                        'wire' => $wire,
                    ]);

                    // send wire email receipt to sender
                    Dispatcher::trigger('wire-receipt:email', 'wire', [
                        'wire' => $wire,
                    ]);

                    //send notification to sender
                    Dispatcher::trigger('notification', 'wire', [
                        'to' => [$senderUser->guid],
                        'from' => $receiverUser->guid,
                        'notification_view' => 'wire_happened',
                        'params' => [
                            'amount' => $this->getAmountString($wire),
                            'from_guid' => $senderUser->guid,
                            'from_username' => $senderUser->username,
                            'to_guid' => $receiverUser->guid,
                            'to_username' => $receiverUser->username,
                            'subscribed' => $data['subscribed'],
                        ],
                    ]);
                }

                echo "Succesfully dispatched wire notifications\n\n";
            });
    }

    private function getAmountString($wire)
    {
        $amount = $wire->getAmount();
        if ($wire->getMethod() === 'tokens') {
            $amount = BigNumber::fromPlain($wire->getAmount(), 18)->toDouble();
            $currency = $amount === 1 ? 'token' : 'tokens';
        } else {
            $currency = strtoupper($wire->getMethod());
        }

        if ($wire->getMethod() === 'usd') {
            $amount = $amount / 100;
        }

        return "$amount $currency";
    }
}
