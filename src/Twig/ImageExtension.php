<?php

namespace App\Twig;

use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ImageExtension extends AbstractExtension
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public function __construct(
        private readonly Packages $packages,
        KernelInterface $kernel,
    ) {
        $this->projectDir = $kernel->getProjectDir();
    }

    private readonly string $projectDir;

    public function getFunctions(): array
    {
        return [
            new TwigFunction('activity_image_url', $this->activityImageUrl(...)),
            new TwigFunction('category_image_url', $this->categoryImageUrl(...)),
        ];
    }

    public function activityImageUrl(?string $image): string
    {
        return $this->resolveImageUrl(
            $image,
            'uploads/activities/',
            '/assets/images/activity-placeholder.svg'
        );
    }

    public function categoryImageUrl(?string $image): string
    {
        return $this->resolveImageUrl(
            $image,
            'uploads/categories/',
            '/assets/images/category-placeholder.svg'
        );
    }

    private function resolveImageUrl(?string $image, string $uploadDirectory, string $placeholderPath): string
    {
        $image = null !== $image ? trim($image) : null;

        if (null === $image || '' === $image) {
            return $this->packages->getUrl(ltrim($placeholderPath, '/'));
        }

        if ($this->isExternalUrl($image)) {
            if ($this->isBlockedPlaceholderUrl($image)) {
                return $this->packages->getUrl(ltrim($placeholderPath, '/'));
            }

            return $image;
        }

        $image = $this->normalizeLocalImageValue($image, $uploadDirectory);

        if (!$this->isLocalFilenameAllowed($image)) {
            return $this->packages->getUrl(ltrim($placeholderPath, '/'));
        }

        $absolutePath = $this->projectDir.'/public/'.$uploadDirectory.$image;
        if (!is_file($absolutePath)) {
            return $this->packages->getUrl(ltrim($placeholderPath, '/'));
        }

        return $this->packages->getUrl($uploadDirectory.$image);
    }

    private function isExternalUrl(string $image): bool
    {
        return str_starts_with($image, 'http://') || str_starts_with($image, 'https://');
    }

    private function isBlockedPlaceholderUrl(string $image): bool
    {
        $host = parse_url($image, PHP_URL_HOST);

        return is_string($host) && 'placehold.co' === strtolower($host);
    }

    private function isLocalFilenameAllowed(string $image): bool
    {
        if (str_contains($image, '/') || str_contains($image, '\\')) {
            return false;
        }

        $extension = strtolower((string) pathinfo($image, PATHINFO_EXTENSION));

        return '' !== $extension && in_array($extension, self::ALLOWED_EXTENSIONS, true);
    }

    private function normalizeLocalImageValue(string $image, string $uploadDirectory): string
    {
        $normalizedDirectory = trim($uploadDirectory, '/').'/';
        $normalizedImage = ltrim(str_replace('\\', '/', $image), '/');

        if (str_starts_with($normalizedImage, $normalizedDirectory)) {
            return basename($normalizedImage);
        }

        return $image;
    }
}
