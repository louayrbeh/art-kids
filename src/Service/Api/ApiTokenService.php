<?php

namespace App\Service\Api;

use App\Entity\User;

class ApiTokenService
{
    private const TTL_SECONDS = 86400;

    public function __construct(private readonly string $secret)
    {
    }

    public function createToken(User $user): string
    {
        $payload = [
            'sub' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'iat' => time(),
            'exp' => time() + self::TTL_SECONDS,
        ];

        $encodedPayload = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = $this->sign($encodedPayload);

        return $encodedPayload.'.'.$signature;
    }

    /**
     * @return array{sub:int,email:string,roles:list<string>,iat:int,exp:int}
     */
    public function parseToken(string $token): array
    {
        [$encodedPayload, $signature] = array_pad(explode('.', $token, 2), 2, null);

        if (!$encodedPayload || !$signature || !hash_equals($this->sign($encodedPayload), $signature)) {
            throw new \RuntimeException('Token API invalide.');
        }

        $payload = json_decode($this->base64UrlDecode($encodedPayload), true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($payload) || !isset($payload['sub'], $payload['email'], $payload['exp'])) {
            throw new \RuntimeException('Token API invalide.');
        }

        if ((int) $payload['exp'] < time()) {
            throw new \RuntimeException('Token API expire.');
        }

        return $payload;
    }

    public function getTtlSeconds(): int
    {
        return self::TTL_SECONDS;
    }

    private function sign(string $encodedPayload): string
    {
        return $this->base64UrlEncode(hash_hmac('sha256', $encodedPayload, $this->secret, true));
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;
        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        if (false === $decoded) {
            throw new \RuntimeException('Token API invalide.');
        }

        return $decoded;
    }
}
