<?php

namespace App\EventListener;

use App\Entity\ForumImage;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsEntityListener(event: Events::preRemove, entity: ForumImage::class)]
class ForumImageListener
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/public/uploads/forum')]
        private string $uploadDir
    ) {
    }

    public function preRemove(ForumImage $image): void
    {
        $filePath = $this->uploadDir . '/' . $image->getFilename();
        
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}