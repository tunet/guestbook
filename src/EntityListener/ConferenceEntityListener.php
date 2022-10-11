<?php

namespace App\EntityListener;

use App\Entity\Conference;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\String\Slugger\SluggerInterface;

class ConferenceEntityListener
{
    public function __construct(
        private readonly SluggerInterface $slugger,
    ) {
    }

    public function prePersist(Conference $conference, LifecycleEventArgs $args): void
    {
        $conference->computeSlug($this->slugger);
    }

    public function preUpdate(Conference $conference, LifecycleEventArgs $args): void
    {
        $conference->computeSlug($this->slugger);
    }
}
