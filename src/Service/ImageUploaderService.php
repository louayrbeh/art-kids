<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class ImageUploaderService
{
    public function __construct(
        private readonly SluggerInterface $slugger,
        private readonly string $categoriesImagesDirectory,
        private readonly string $activitiesImagesDirectory,
    ) {
    }

    public function uploadCategoryImage(UploadedFile $file): string
    {
        return $this->upload($file, $this->categoriesImagesDirectory, 'category');
    }

    public function uploadActivityImage(UploadedFile $file): string
    {
        return $this->upload($file, $this->activitiesImagesDirectory, 'activity');
    }

    public function deleteCategoryImage(?string $filename): void
    {
        $this->deleteFile($this->categoriesImagesDirectory, $filename);
    }

    public function deleteActivityImage(?string $filename): void
    {
        $this->deleteFile($this->activitiesImagesDirectory, $filename);
    }

    private function upload(UploadedFile $file, string $targetDirectory, string $prefix): string
    {
        $this->ensureDirectoryExists($targetDirectory);

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename)->lower()->toString();
        $safeFilename = '' !== $safeFilename ? $safeFilename : $prefix;
        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin';
        $newFilename = sprintf('%s-%s.%s', $safeFilename, uniqid($prefix.'_', true), $extension);

        try {
            $file->move($targetDirectory, $newFilename);
        } catch (FileException $exception) {
            throw new \RuntimeException('Impossible d uploader l image pour le moment.', 0, $exception);
        }

        return $newFilename;
    }

    private function deleteFile(string $directory, ?string $filename): void
    {
        if (null === $filename || '' === $filename) {
            return;
        }

        $path = $directory.DIRECTORY_SEPARATOR.$filename;
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (!@mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Impossible de creer le dossier d upload "%s".', $directory));
        }
    }
}
