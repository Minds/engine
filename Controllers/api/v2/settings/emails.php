<?php
namespace Minds\Controllers\api\v2\settings;

use Minds\Api\Factory;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Email\Confirmation\Manager as EmailConfirmation;
use Minds\Core\Email\EmailSubscription;
use Minds\Core\Entities\Actions\Save;
use Minds\Entities\User;
use Minds\Interfaces;

class emails implements Interfaces\Api
{
    public function get($pages)
    {
        $user = Core\Session::getLoggedInUser();
        if (!$user) {
            return Factory::response([
                'status' => 'error',
                'message' => 'User must be logged in.'
            ]);
        }

        $campaigns = [ 'when', 'with', 'global' ];

        $topics = [
            'unread_notifications',
            'wire_received',
            'boost_completed',
            'top_posts',
            'channel_improvement_tips',
            'posts_missed_since_login',
            'new_channels',
            'minds_news',
            'minds_tips',
            'exclusive_promotions',
        ];

        /** @var Core\Email\Repository $rpository */
        $repository = Di::_()->get('Email\Repository');
        $result = $repository->getList([
            'campaigns' => $campaigns,
            'topics' => $topics,
            'user_guid' => $user->guid,
        ]);

        $response = [
            'email' => $user->getEmail(),
            'notifications' => Factory::exportable($result['data']),
        ];

        return Factory::response($response);
    }

    public function post($pages)
    {
        if (Core\Session::getLoggedInUser()->isAdmin() && isset($pages[0])) {
            $user = new User($pages[0]);
        } else {
            $user = Core\Session::getLoggedInUser();
        }

        if (isset($_POST['email']) && $_POST['email']) {
            if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                return Factory::response([
                    'status' => 'error',
                    'message' => 'Invalid e-mail address'
                ]);
            }

            $emailChange = strtolower($_POST['email']) !== strtolower($user->getEmail());

            $user->setEmail($_POST['email']);
            (new Save())->setEntity($user)->withMutatedAttributes(['email'])->save();

            if ($emailChange) {
                /** @var EmailConfirmation $emailConfirmation */
                $emailConfirmation = Di::_()->get('Email\Confirmation');
                $emailConfirmation
                    ->setUser($user);

                $reset = $emailConfirmation
                    ->reset();

                if ($reset) {
                    $emailConfirmation->generateConfirmationToken();
                } else {
                    error_log('Cannot reset email confirmation for ' . $user->guid);
                }
            }
        }

        if (isset($_POST['notifications'])) {
            /** @var Core\Email\Repository $repository */
            $repository = Di::_()->get('Email\Repository');
            $notifications = $_POST['notifications'];

            foreach ($notifications as $campaign => $topics) {
                foreach ($topics as $topic => $value) {
                    $val = (string) $value;
                    $model = new EmailSubscription();
                    $model->setUserGuid($user->guid)
                        ->setCampaign($campaign)
                        ->setTopic($topic)
                        ->setValue($val !== '' ? $val : '0');

                    try {
                        $repository->add($model);
                    } catch (\Exception $e) {
                        return Factory::response([
                            'status' => 'error',
                            'message' => $e->getMessage()
                        ]);
                    }
                }
            }
        }

        return Factory::response([]);
    }

    public function put($pages)
    {
        return Factory::response([]);
    }

    public function delete($pages)
    {
        return Factory::response([]);
    }
}
