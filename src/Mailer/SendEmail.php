<?php

namespace App\Mailer;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class SendEmail
{


    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }



    public function sendEmail($to, $title, $text)
    {
        $email = (new Email())
            ->from(new Address('devops@kpsport.com', 'DEVOPS'))
            ->to(...$to)
            ->subject("[PLATFORM B2B] ".$title)
            ->html($text);
        $this->mailer->send($email);
    }


    public function sendAlert($title, $text)
    {
        $this->sendEmail(["devops@kpsport.com"], $title, $text);
    }

}
