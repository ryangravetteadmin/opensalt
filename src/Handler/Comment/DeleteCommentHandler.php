<?php

namespace App\Handler\Comment;

use App\Command\Comment\DeleteCommentCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DeleteCommentHandler extends BaseCommentHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var DeleteCommentCommand $command */
        $command = $event->getCommand();

        $comment = $command->getComment();
        $this->em->remove($comment);

        /* @todo update to fill in name and document after comments are modified */
        $notification = new NotificationEvent('C03', 'Comment deleted' /* for [Short name] */, null);
        $command->setNotificationEvent($notification);
    }
}
