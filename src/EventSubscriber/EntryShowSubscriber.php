<?php declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Entry;
use App\Entity\Notification;
use App\Event\EntryHasBeenSeenEvent;
use App\Repository\EntryNotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Security;
use Tchoulom\ViewCounterBundle\Counter\ViewCounter as Counter;

class EntryShowSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Counter $viewCounter,
        private Security $security,
        private EntryNotificationRepository $repository,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[ArrayShape([EntryHasBeenSeenEvent::class => "string"])] public static function getSubscribedEvents(): array
    {
        return [
            EntryHasBeenSeenEvent::class => 'onShowEntry',
        ];
    }

    public function onShowEntry(EntryHasBeenSeenEvent $event): void
    {
        $this->saveView($event->entry);
        $this->readMessage($event->entry);
    }

    private function saveView(Entry $entry): void
    {
        try {
            $this->viewCounter->saveView($entry);
        } catch (Exception $e) {
        }
    }

    private function readMessage(Entry $entry): void
    {
        if (!$this->security->getUser()) {
            return;
        }

        $notification = $this->repository->findNewEntryUnreadNotification($this->security->getUser(), $entry);

        if (!$notification) {
            return;
        }

        if ($notification->getType() !== 'entry_notification') {
            return;
        }

        $notification->status = Notification::STATUS_READ;

        $this->entityManager->flush();
    }
}
