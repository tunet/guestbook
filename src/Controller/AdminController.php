<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Message\CommentMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Workflow\Registry;
use Twig\Environment;

class AdminController extends AbstractController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $bus,
    ) {
    }

    #[Route('/admin/comment/review/{id}', name: 'review_comment')]
    public function reviewComment(Request $request, Comment $comment, Registry $registry): Response
    {
        $accepted = !$request->query->get('reject');
        $machine = $registry->get($comment);

        if ($machine->can($comment, 'publish')) {
            $transition = $accepted ? 'publish' : 'reject';
        } elseif ($machine->can($comment, 'publish_ham')) {
            $transition = $accepted ? 'publish_ham' : 'reject_ham';
        } else {
            return new Response('Comment already reviewed or not in the right state.');
        }

        $machine->apply($comment, $transition);
        $this->entityManager->flush();

        if ($accepted) {
            $reviewUrl = $this->generateUrl(
                'review_comment',
                ['id' => $comment->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL,
            );
            $this->bus->dispatch(new CommentMessage($comment->getId(), $reviewUrl));
        }

        return new Response($this->twig->render('admin/review.html.twig', [
            'transition' => $transition,
            'comment' => $comment,
        ]));
    }
}
