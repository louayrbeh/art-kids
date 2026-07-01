<?php

namespace App\Service;

class ActivityAiService
{
    public function generateDescription(string $title, ?string $category = null): string
    {
        $categoryPart = $category ? sprintf(' dans la categorie %s', mb_strtolower($category)) : '';

        // TODO connect a real OpenAI-powered generator when API credentials are available.
        return sprintf(
            'Atelier "%s"%s, pense pour les enfants, avec une approche ludique, creative et adaptee a leur age.',
            $title,
            $categoryPart
        );
    }
}
