<?php

namespace App\Listener;

use Doctrine\Common\EventArgs;
use Gedmo\Loggable\LoggableListener;
use Symfony\Bundle\SecurityBundle\Security;

class UserLoggableListener extends LoggableListener
{
    private $security;
    public function setSecurity(Security $security)
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
