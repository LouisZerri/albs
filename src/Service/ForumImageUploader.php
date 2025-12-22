<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class ForumImageUploader
{
    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5 Mo
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private const MAX_IMAGES_PER_POST = 3;

    public function __construct(
        private string $projectDir,
        private SluggerInterface $slugger
    ) {
    }

    public function upload(UploadedFile $file): array
    {
        // Valider l'extension
        $extension = strtolower($file->guessExtension() ?? $file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            return [
                'success' => false,
                'error' => 'Format non autorisé. Formats acceptés : ' . implode(', ', self::ALLOWED_EXTENSIONS)
            ];
        }

        // Récupérer la taille AVANT de déplacer le fichier
        $fileSize = $file->getSize();
        $originalFilename = $file->getClientOriginalName();

        // Valider la taille
        if ($fileSize > self::MAX_FILE_SIZE) {
            return [
                'success' => false,
                'error' => 'Fichier trop volumineux. Taille max : 5 Mo'
            ];
        }

        // Générer un nom unique
        $safeFilename = $this->slugger->slug(pathinfo($originalFilename, PATHINFO_FILENAME));
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;

        // Créer le dossier si nécessaire
        $uploadDir = $this->projectDir . '/public/uploads/forum';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Déplacer le fichier
        try {
            $file->move($uploadDir, $newFilename);
        } catch (FileException $e) {
            return [
                'success' => false,
                'error' => 'Erreur lors de l\'upload : ' . $e->getMessage()
            ];
        }

        return [
            'success' => true,
            'filename' => $newFilename,
            'originalFilename' => $originalFilename,
            'fileSize' => $fileSize
        ];
    }

    public function delete(string $filename): bool
    {
        $filepath = $this->projectDir . '/public/uploads/forum/' . $filename;
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return false;
    }

    public function getMaxImagesPerPost(): int
    {
        return self::MAX_IMAGES_PER_POST;
    }
}