<?php
declare(strict_types=1);

namespace Minds\Core\Email\Controllers;

use Minds\Entities\User;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response\JsonResponse;

class EmailAddressController
{
    /**
     * Get email address.
     * @param ServerRequest $request - server request object.
     * @return JsonResponse - contains status success on success.
     */
    public function getEmailAddress(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        return new JsonResponse([
            'status' => 'success',
            'email' => $user->getEmail()
        ]);
    }
}
