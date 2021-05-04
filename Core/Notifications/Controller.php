<?php
namespace Minds\Core\Notifications;

use Minds\Entities\User;
use Minds\Core\Di\Di;
use Exception;
use Minds\Exceptions\UserErrorException;
use Minds\Core\Notifications\Notification;
use Minds\Core\Notifications\Manager;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * Notifications Controller
 * @package Minds\Core\Notifications
 */
class Controller
{
    /** @var Manager */
    protected $manager;

    /**
     * Controller constructor.
     * @param null $manager
     */
    public function __construct(
        $manager = null,
    ) {
        $this->manager = $manager ?? new Manager();
    }

    //ojm from routes

    // getSingle() {
    //                                 //.get(`api/v1/notifications/single/${guid}`)
    // }

    // getAll(){
        // this filter is 'all'
    //   .get(`api/v1/notifications/${this._filter}`, {
    //     limit: this.limit,
    //     offset: this.offset,
    //   })
    // }
}
