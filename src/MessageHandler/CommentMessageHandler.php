<?php

namespace App\MessageHandler;

use App\ImageOptimizer;
use App\Message\CommentMessage;
use App\Notification\CommentReviewNotification;
use App\Repository\CommentRepository;
use App\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class CommentMessageHandler implements MessageHandlerInterface
{
    private readonly WorkflowInterface $workflow;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SpamChecker $spamChecker,
        private readonly CommentRepository $commentRepository,
        private readonly MessageBusInterface $bus,
        private readonly LoggerInterface $logger,
        WorkflowInterface $commentStateMachine,
        private readonly MailerInterface $mailer,
        private readonly string $adminEmail,
        private readonly ImageOptimizer $imageOptimizer,
        private readonly string $photoDir,
        private readonly NotifierInterface $notifier,
    ) {
        $this->workflow = $commentStateMachine;
    }

    public function __invoke(CommentMessage $message): void
    {
        $comment = $this->commentRepository->find($message->id);

        if (!$comment) {
            return;
        }

        if ($this->workflow->can($comment, 'accept')) {
            $score = $this->spamChecker->getSpamScore($comment, $message->context);
            $transition = 'accept';

            if (2 === $score) {
                $transition = 'reject_spam';
            } elseif (1 === $score) {
                $transition = 'might_be_spam';
            }

            $this->workflow->apply($comment, $transition);
            $this->entityManager->flush();

            $this->bus->dispatch($message);
        } elseif ($this->workflow->can($comment, 'publish') || $this->workflow->can($comment, 'publish_ham')) {
            $notification = new CommentReviewNotification($comment, $message->reviewUrl);
            $this->notifier->send($notification, ...$this->notifier->getAdminRecipients());
        } elseif ($this->workflow->can($comment, 'optimize')) {
            if ($comment->getPhotoFilename()) {
                $this->imageOptimizer->resize($this->photoDir.'/'.$comment->getPhotoFilename());
            }

            $this->workflow->apply($comment, 'optimize');
            $this->entityManager->flush();
        } else {
            $this->logger->debug(
                'Dropping comment message',
                [
                    'comment' => $comment->getId(),
                    'state'   => $comment->getState(),
                ],
            );
        }
    }
}
