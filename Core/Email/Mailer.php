<?php
/**
 * Email mailer
 */
namespace Minds\Core\Email;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Queue\Client as Queue;
use Minds\Entities;
use PHPMailer;

class Mailer
{
    private $mailer;
    private $queue;
    private $stats;

    public function __construct($mailer = null, $queue = null, $filter = null)
    {
        $this->mailer = $mailer ?: new PHPMailer();
        $this->setup();
        $this->stats = [
          'sent' => 0,
          'failed' => 0
        ];
        $this->queue = $queue ?: Queue::build();
        $this->filter = $filter ?: Di::_()->get('Email\SpamFilter');
    }

    private function setup()
    {
        $this->mailer->isSMTP();
        //$this->mailer->SMTPKeepAlive = true;
        $this->mailer->Host = Core\Config::_()->email['smtp']['host'];
        $this->mailer->Auth = Core\Config::_()->email['smtp']['auth'];
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = Core\Config::_()->email['smtp']['username'];
        $this->mailer->Password = Core\Config::_()->email['smtp']['password'];
        $this->mailer->SMTPSecure = "ssl";
        $this->mailer->Port = Core\Config::_()->email['smtp']['port'];
    }

    /**
     * Send an email
     * @param Message $message
     * @return $this
     */
    public function send($message)
    {
        $this->mailer->ClearAllRecipients();
        $this->mailer->ClearAttachments();

        $this->mailer->From = $message->from['email'];
        $this->mailer->FromName = $message->from['name'];

        foreach ($message->to as $to) {
            if ($this->filter->isSpam($to['email'])) {
                continue; //don't send to blacklisted domains
            }
            $this->mailer->AddAddress($to['email'], $to['name']);
        }

        $this->mailer->MessageID = $message->messageId;
        $this->mailer->Subject = $message->subject;

        $this->mailer->IsHTML(true);
        $this->mailer->Body = $message->buildHtml();
        $this->mailer->CharSet = 'utf-8';

        if ($this->mailer->Send()) {
            $this->stats['sent']++;
        } else {
            $this->stats['failed']++;
        }

        return $this;
    }

    public function queue($message, $priority = false)
    {
        $queueName = $priority ? 'PriorityEmail' : 'Email';
        try {
            $this->queue->setQueue($queueName)
                ->send([
                    "message" => serialize($message)
                ]);
        } catch (\Exception $e) {
            var_dump($e); exit;
        }
    }

    public function getStats()
    {
        return $this->stats;
    }

    public function __destruct()
    {
        if ($this->mailer) {
            $this->mailer->SmtpClose();
        }
    }
}
