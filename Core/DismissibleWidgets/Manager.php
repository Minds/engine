<?php
namespace Minds\Core\DismissibleWidgets;

use Minds\Core\Di\Di;
use Minds\Core\Session;
use Minds\Core;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Config;
use Minds\Api\Exportable;
use Minds\Core\Entities\Actions;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response\JsonResponse;

class Manager
{
    /** @var string[] */
    const WIDGET_IDS = [
        'test-widget-id',
        'discovery-disclaimer-2020'
    ];

    /** @var User */
    protected $user;

    /** @var Actions\Save */
    protected $save;

    public function __construct(
        $user = null,
        $save = null
    ) {
        $this->user = $user ?? Session::getLoggedInUser();
        $this->save = $save ?? new Actions\Save();
    }

    /**
     * Adds widget to list of dismissed ids
     * @param string $id
     * @return bool
     */
    public function setDimissedId(string $id): bool
    {
        if (!in_array($id, static::WIDGET_IDS, true)) {
            throw new InvalidWidgetIDException();
        }

        $ids = $this->user->getDismissedWidgets();
        $ids[] = $id;

        $this->user->setDismissedWidgets(array_unique($ids));

        return $this->save
            ->setEntity($this->user)
            ->save();
    }
}
