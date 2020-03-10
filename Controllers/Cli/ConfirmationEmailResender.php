<?php
declare(ticks=1);

/**
 * ConfirmationEmailResender CLI
 *
 * @author eiennohi
 */

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Common\Urn;
use Minds\Core\Data\ElasticSearch\Client;
use Minds\Core\Data\ElasticSearch\Prepared\Search;
use Minds\Core\Di\Di;
use Minds\Core\Email\Confirmation\Manager;
use Minds\Core\Entities\Resolver;
use Minds\Interfaces;

class ConfirmationEmailResender extends Cli\Controller implements Interfaces\CliControllerInterface
{
    /**
     * Echoes $commands (or overall) help text to standard output.
     * @param string|null $command - the command to be executed. If null, it corresponds to exec()
     * @return null
     */
    public function help($command = null)
    {
        $this->out('Usage: cli ConfirmationEmailResender');
    }

    /**
     * Executes the default command for the controller.
     * @return mixed
     */
    public function exec()
    {
        \Minds\Core\Events\Defaults::_();

        $resolver = new Resolver();

        /** @var Client */
        $client = Di::_()->get('Database\ElasticSearch');

        /** @var Manager $manager */
        $manager = Di::_()->get('Email\Confirmation');

        $must = [
            [
                'range' => [
                    'time_created' => [
                        'lt' => strtotime('midnight today'),
                        'gte' => strtotime('midnight yesterday'),
                    ],
                ],

            ],
        ];

        $must_not = [
            [
                'exists' => [
                    'field' => 'email_confirmed_at',
                ],
            ],
        ];

        $query = [
            'index' => 'minds_badger',
            'type' => 'user',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => $must,
                        'must_not' => $must_not,
                    ],
                ],
            ],
        ];

        $prepared = new Search();
        $prepared->query($query);

        $result = $client->request($prepared);

        $urns = [];

        if (!isset($result) || !isset($result['hits']) || !isset($result['hits']['hits'])) {
            $this->out("[ConfirmationEmailResender]: No newly registered users found");
            return;
        }

        foreach ($result['hits']['hits'] as $r) {
            try {
                $urns[] = new Urn('urn:user:' . $r['_source']['guid']);
            } catch (\Exception $e) {
                $this->out("[ConfirmationEmailResender] Exception: {$e->getMessage()}");
            }
        }

        $resolver->setUrns($urns);
        $users = $resolver->fetch();

        if (!isset($users) || count($users) === 0) {
            $this->out("[ConfirmationEmailResender]: Resolver returned no users");
            return;
        }

        foreach ($users as $user) {
            if ($user->isEmailConfirmed()) {
                $this->out("[ConfirmationEmailResender]: User email already confirmed ({$user->guid}))");
                continue;
            }
            // try to resend the email
            $manager
                ->setUser($user)
                ->sendEmail();

            $this->out("[COnfirmationEmailResender]: Email sent to {$user->guid}");
        }
    }
}
