<?php

namespace App\Service;

class ExternalImageService
{
    public function getPlaceholder(?string $query = null): string
    {
        $label = rawurlencode($query ?: 'Art Kids');

        return sprintf('https://placehold.co/800x500?text=%s', $label);
    }
}
