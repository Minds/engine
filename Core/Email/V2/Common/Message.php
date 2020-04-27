<?php
/**
 * Message item.
 */

namespace Minds\Core\Email\V2\Common;

use Minds\Entities\User;
use Minds\Traits\MagicAttributes;

class Message
{
    use MagicAttributes;
    public $from = [];
    public $to = [];
    public $replyTo = [];
    public $subject = '';
    public $html = '';
    public $messageId = '';

    public function __construct()
    {
        $this->init();
    }

    private function init()
    {
        $this->from = [
          'name' => 'Minds',
          'email' => 'no-reply@minds.com',
        ];
    }

    /**
     * Set from data.
     *
     * @param string $email
     * @param string $name
     *
     * @return $this
     */
    public function setFrom($email, $name = 'Minds')
    {
        $this->from = [
        'name' => $name,
        'email' => $email,
      ];

        return $this;
    }

    /**
     * Set to data.
     *
     * @param User $user
     *
     * @return $this
     */
    public function setTo($user)
    {
        $this->to[] = [
        'name' => $user->name,
        'email' => $user->getEmail(),
      ];

        return $this;
    }

    /**
     * Set subject data.
     *
     * @param string $subject
     *
     * @return $this
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Set Message ID sender data.
     *
     * @return $this
     */
    public function setMessageId($messageId)
    {
        $this->messageId = $messageId ? '<'.$messageId.'@minds.com>' : '';

        return $this;
    }

    /**
     * Set html data.
     *
     * @param string $html
     *
     * @return $this
     */
    public function setHtml(Template $html)
    {
        $this->html = $html;
    }

    /**
     * Set html data.
     *
     * @return $html
     */
    public function buildHtml()
    {
        return $this->html->render();
    }

    /**
     * Get reply-to.
     *
     * @return array array containing email and username.
     */
    public function getReplyTo()
    {
        return $this->replyTo ?? [];
    }
    /**
     * Set reply-to.
     *
     * @param string $email - the email address for the reply.
     * @param string $name - the name to be replied to.
     *
     * @return Message returns $this instance for chaining.
     */
    public function setReplyTo($email, $name = 'Minds'): Message
    {
        $this->replyTo['email'] = $email;
        $this->replyTo['name'] = $name;
        return $this;
    }
}
