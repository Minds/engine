<?php
namespace Minds\Core\Email\SendGrid\Lists;

use Minds\Core\Matrix\Manager as MatrixManager;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Di\Di;
use Minds\Core\Email\SendGrid\SendGridContact;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;

/**
 * Users who have an account on matrix
 */
class MatrixList implements SendGridListInterface
{
    public function __construct(
        private ?MatrixManager $matrixManager = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?Client $mysqlClient = null,
    ) {
        $this->matrixManager = Di::_()->get('Matrix\Manager');
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->mysqlClient ??= Di::_()->get("Database\MySQL\Client");
    }

    public function getContacts(): iterable
    {
        foreach ($this->matrixManager->getAccounts() as $matrixAccount) {
            $user = $this->entitiesBuilder->getByUserByIndex($matrixAccount->getId());

            if (!$user instanceof User) {
                continue;
            }

            $contact = new SendGridContact();
            $contact
                ->setUser($user)
                ->setUserGuid($user->getGuid())
                ->setUsername($user->getUsername())
                ->setEmail($user->getEmail())
                ->set('used_matrix', true);

            yield $contact;
        }

        return;
    }
}
