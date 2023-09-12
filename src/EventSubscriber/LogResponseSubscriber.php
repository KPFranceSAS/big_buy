<?php

namespace App\EventSubscriber;

use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterCrudActionEvent;
use Gedmo\Loggable\Entity\LogEntry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LogResponseSubscriber implements EventSubscriberInterface
{
    private $manager;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->manager = $managerRegistry->getManager();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AfterCrudActionEvent::class => ['addLogs'],
        ];
    }

    public function addLogs(AfterCrudActionEvent $event)
    {
        $context = $event->getAdminContext();
        $instance = $context->getEntity()->getInstance();
        if ($instance) {
            $repo = $this->manager->getRepository(LogEntry::class);
            $logs = $repo->getLogEntries($instance);
            $event->getResponseParameters()->set('logs', $logs);
        }
    }
}
