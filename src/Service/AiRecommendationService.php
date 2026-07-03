<?php

namespace App\Service;

use App\Entity\Activity;
use App\Entity\Child;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiRecommendationService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $openAiApiKey = '',
        private readonly string $openAiModel = 'gpt-4o-mini',
    ) {
    }

    public function explainRecommendation(Child $child, Activity $activity): string
    {
        if ('' === trim($this->openAiApiKey)) {
            return $this->generateLocalExplanation($child, $activity);
        }

        try {
            $explanation = $this->generateOpenAiExplanation($child, $activity);
        } catch (\Throwable $exception) {
            $this->logger->warning('AI recommendation explanation failed, local fallback used.', [
                'exception' => $exception,
                'child_id' => $child->getId(),
                'activity_id' => $activity->getId(),
            ]);

            return $this->generateLocalExplanation($child, $activity);
        }

        $explanation = trim((string) $explanation);

        if ('' === $explanation) {
            return $this->generateLocalExplanation($child, $activity);
        }

        return preg_replace('/\s+/', ' ', $explanation) ?? $explanation;
    }

    private function generateOpenAiExplanation(Child $child, Activity $activity): string
    {
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
                        'content' => 'Tu es un assistant specialise dans les recommandations d activites artistiques pour enfants.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->buildPrompt($child, $activity),
                    ],
                ],
                'temperature' => 0.6,
                'max_tokens' => 180,
            ],
            'timeout' => 20,
        ]);

        $statusCode = $response->getStatusCode();
        $payload = $response->toArray(false);

        if ($statusCode >= 400) {
            $this->logger->warning('OpenAI returned an error response for activity recommendation explanation.', [
                'status_code' => $statusCode,
                'payload' => $payload,
            ]);

            throw new \RuntimeException('OpenAI returned an error response.');
        }

        $content = $payload['choices'][0]['message']['content'] ?? null;

        if (!is_string($content) || '' === trim($content)) {
            throw new \RuntimeException('OpenAI returned an empty explanation.');
        }

        return $content;
    }

    private function buildPrompt(Child $child, Activity $activity): string
    {
        $category = $activity->getCategory()?->getNom() ?: 'artistique';
        $date = $activity->getDateActivite()?->format('d/m/Y') ?: 'non precisee';

        return <<<PROMPT
Tu es un assistant specialise dans les recommandations d activites artistiques pour enfants.

Genere une courte explication en francais destinee au parent.

Donnees :
- Prenom de l enfant : {$child->getPrenom()}
- Age de l enfant : {$child->getAge()}
- Activite : {$activity->getTitre()}
- Categorie : {$category}
- Age minimum : {$activity->getAgeMin()}
- Age maximum : {$activity->getAgeMax()}
- Date : {$date}
- Places disponibles : {$activity->placesDisponibles()}

Contraintes :
- 2 a 4 phrases maximum.
- Ton positif, clair et rassurant.
- Ne pas inventer d informations.
- Ne pas mentionner de donnees absentes.
- Expliquer pourquoi cette activite correspond a l age et au developpement de l enfant.
- Mettre en avant la creativite, la confiance et l apprentissage.
PROMPT;
    }

    private function generateLocalExplanation(Child $child, Activity $activity): string
    {
        $category = $activity->getCategory()?->getNom();
        $childName = $child->getPrenom() ?: 'votre enfant';
        $base = $category
            ? sprintf('Cette activite de %s est recommandee pour %s car elle correspond bien a son age et a son rythme d apprentissage.', mb_strtolower($category), $childName)
            : sprintf('Cette activite artistique est recommandee pour %s car elle correspond bien a son age et a son rythme d apprentissage.', $childName);

        $creative = 'Elle favorise la creativite, la concentration et la confiance en soi dans un cadre bienveillant.';
        $places = $activity->placesDisponibles() > 1
            ? 'Des places sont encore disponibles pour cette activite.'
            : ($activity->placesDisponibles() === 1 ? 'Il reste encore une place disponible pour cette activite.' : '');

        return trim($base.' '.$creative.' '.$places);
    }
}
