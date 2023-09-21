<?php

namespace App\Listener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Common\EventArgs;
use Doctrine\ORM\Events;
use Gedmo\Loggable\LoggableListener;
use Symfony\Bundle\SecurityBundle\Security;

#[AsDoctrineListener(event: Events::onFlush, priority: 500, connection: 'default')]
class UserLoggableListener extends LoggableListener
{
    private $security;
    public function __construct(Security $security)
    {
        $this->security = $security;
    }


    public function onFlush(EventArgs $eventArgs)
    {
        if ($this->security->getUser()) {
            $this->setUsername($this->security->getUser());
        }

        parent::onFlush($eventArgs);
    }
}
