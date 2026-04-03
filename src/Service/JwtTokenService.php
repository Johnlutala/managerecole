<?php 

namespace App\Service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Symfony\Component\HttpFoundation\Request;

class JwtTokenService
{
    public function __construct(
        private string $secretKey,
        private string $algo
    ) {
    }

    /**
     * Génère un token pour un utilisateur
     */
    public function generateToken(array $claims): string
    {
        $payload = [
            'iat'  => time(),
            'exp'  => time() + 1800, // 30 minutes
            'shortcode'  => $claims['shortcode'],
            'username' => $claims['username'],
        ];

        return JWT::encode($payload, $this->secretKey, $this->algo);
    }

    /**
     * Extrait le token du header Authorization (Bearer <token>)
     */
    public function extractTokenFromRequest(Request $request): ?string
    {
        $authHeader = $request->headers->get('x-api-token');


        if (!$authHeader || str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        return $authHeader;
    }

    /**
     * Vérifie si le token est valide et retourne les données décodées
     * @throws \Exception en cas de token invalide ou expiré
     */
    public function validateAndDecode(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algo));
            
            // Retourne le payload sous forme de tableau
            return (array) $decoded;
            
        } catch (ExpiredException $e) {
            //throw new \Exception("Le token a expiré le " . date('Y-m-d H:i:s', $e->getPayload()->exp));
            return [
                "error" => "Token expiré"
            ];

        } catch (SignatureInvalidException $e) {
            //throw new \Exception("La signature du token est invalide.");
            return [
                "error" => "Token invalide"
            ];
        } catch (\Exception $e) {
            //throw new \Exception("Erreur lors de la validation du token : " . $e->getMessage());
            return [
                "error" => "Token invalide"
            ];
        }
    }
}