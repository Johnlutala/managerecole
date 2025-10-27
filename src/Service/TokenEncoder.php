<?php 

namespace App\Service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;


class TokenEncoder
{
    
    private string $secretKey;
    
    public function __construct(
        string $secretKey
    ){
        $this->secretKey = $secretKey;
    }


    public function generateToken(
        string $expiration,
        string $username
    ): string
    {
        $now = new \DateTimeImmutable();
        $payload = [
            'iat' => $now->getTimestamp(),
            'exp' => $now->modify($expiration)->getTimestamp(),
            'username' => $username
        ];

        return JWT::encode(
            $payload,
            $this->secretKey,
            'HS256'
        );
    }

    private function decode(string $token): array
    {
        $credentials = (array)JWT::decode($token, new Key($this->secretKey, 'HS256'));
        return $credentials;
    }

    public function validateToken(string $token): ?string
    {
        try {
            $credentials = $this->decode($token);
            
            // Vérifier la date d'expiration
            $now = new \DateTimeImmutable();
            if ($credentials['exp'] < $now->getTimestamp()) {
                return null; // Token expiré
            }
            return $credentials['username'];

        } catch (\Throwable $e) {
            return null; // Token invalide
        }
    }
}