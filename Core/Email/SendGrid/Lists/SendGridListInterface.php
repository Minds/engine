<?php
namespace Minds\Core\Email\SendGrid\Lists;

use Minds\Core\Email\SendGrid\SendGridContact;

interface SendGridListInterface
{
    /**
     * @return SendGridContact[]
     */
    public function getContacts(): iterable;
}
