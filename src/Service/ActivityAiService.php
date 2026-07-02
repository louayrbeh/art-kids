<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ActivityAiService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $openAiApiKey = '',
        private readonly string $openAiModel = 'gpt-4o-mini',
    ) {
    }

    public function generateDescription(
        string $title,
        ?string $categoryName = null,
        ?int $ageMin = null,
        ?int $ageMax = null,
    ): string {
        $title = trim($title);
        $categoryName = null !== $categoryName ? trim($categoryName) : null;

        if ('' === $title) {
            throw new \InvalidArgumentException('Le titre est obligatoire pour generer une description.');
        }

        if (null !== $ageMin && $ageMin < 0) {
            throw new \InvalidArgumentException('L age minimum doit etre positif.');
        }

        if (null !== $ageMax && $ageMax < 0) {
            throw new \InvalidArgumentException('L age maximum doit etre positif.');
        }

        if (null !== $ageMin && null !== $ageMax && $ageMax < $ageMin) {
            throw new \InvalidArgumentException('L age maximum doit etre superieur ou egal a l age minimum.');
        }

        if ('' === trim($this->openAiApiKey)) {
            return $this->generateLocalDescription($title, $categoryName, $ageMin, $ageMax);
        }

        try {
            $description = $this->generateOpenAiDescription($title, $categoryName, $ageMin, $ageMax);
        } catch (\Throwable $exception) {
            $this->logger->warning('AI description generation failed, local fallback used.', [
                'exception' => $exception,
                'title' => $title,
                'category' => $categoryName,
                'ageMin' => $ageMin,
                'ageMax' => $ageMax,
            ]);

            return $this->generateLocalDescription($title, $categoryName, $ageMin, $ageMax);
        }

        $description = trim((string) $description);
        if ('' === $description) {
            return $this->generateLocalDescription($title, $categoryName, $ageMin, $ageMax);
        }

        return preg_replace('/\s+/', ' ', $description) ?? $description;
    }

    private function generateOpenAiDescription(
        string $title,
        ?string $categoryName,
        ?int $ageMin,
        ?int $ageMax,
    ): string {
        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer '.$this->openAiApiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => $this->openAiModel,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Tu es un assistant specialise dans la redaction de descriptions professionnelles pour des activites artistiques destinees aux enfants.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->buildPrompt($title, $categoryName, $ageMin, $ageMax),
                    ],
                ],
                'temperature' => 0.7,
                'max_tokens' => 250,
            ],
            'timeout' => 20,
        ]);

        $statusCode = $response->getStatusCode();
        $payload = $response->toArray(false);

        if ($statusCode >= 400) {
            $this->logger->warning('OpenAI returned an error response for activity description generation.', [
                'status_code' => $statusCode,
                'payload' => $payload,
            ]);

            throw new \RuntimeException('OpenAI returned an error response.');
        }

        $content = $payload['choices'][0]['message']['content'] ?? null;
        if (!is_string($content) || '' === trim($content)) {
            throw new \RuntimeException('OpenAI returned an empty description.');
        }

        return $content;
    }

    private function buildPrompt(
        string $title,
        ?string $categoryName,
        ?int $ageMin,
        ?int $ageMax,
    ): string {
        $categoryLabel = $categoryName ?: 'artistique';
        $ageMinLabel = null !== $ageMin ? (string) $ageMin : 'non precise';
        $ageMaxLabel = null !== $ageMax ? (string) $ageMax : 'non precise';

        return <<<PROMPT
Genere une description professionnelle en francais pour une activite artistique destinee aux enfants.

Informations :
- Titre : {$title}
- Categorie : {$categoryLabel}
- Age minimum : {$ageMinLabel}
- Age maximum : {$ageMaxLabel}

Contraintes :
- 4 a 8 phrases.
- Ton clair, positif et rassurant.
- Texte destine aux parents.
- Mettre en avant la creativite, la confiance, l apprentissage et l encadrement.
- Ne pas utiliser de listes.
- Ne pas inventer de prix ou de date.
PROMPT;
    }

    private function generateLocalDescription(
        string $title,
        ?string $categoryName,
        ?int $ageMin,
        ?int $ageMax,
    ): string {
        $categoryLabel = $categoryName ?: 'artistique';
        $ageSentence = null !== $ageMin && null !== $ageMax
            ? sprintf('Elle convient particulierement aux enfants ages de %d a %d ans, avec des propositions adaptees a leur rythme et a leur niveau.', $ageMin, $ageMax)
            : 'Elle convient aux enfants qui souhaitent explorer une activite artistique dans un cadre progressif et rassurant.';

        return sprintf(
            'L activite "%s" est un atelier artistique concu pour aider les enfants a developper leur creativite et leur imagination. Elle s inscrit dans l univers %s et propose une experience ludique, progressive et valorisante. Les participants y decouvrent de nouvelles techniques, expriment leurs idees et gagnent en confiance au fil des seances. %s Encadree dans un environnement bienveillant et securise, cette activite permet aux parents d offrir a leur enfant un moment d apprentissage, d expression personnelle et de plaisir creatif.',
            $title,
            $categoryLabel,
            $ageSentence
        );
    }
}
