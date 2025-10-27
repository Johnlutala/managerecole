<?php 

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\Builder;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Validator;

class TokenGeneration
{

    private InMemory $secretKey;
    private JoseEncoder $encoder;
    private string $issuer;
    private Sha256 $signer;

    public function __construct(string $secretKey, string $issuer = 'flexroll-workshop-api')
    {
        $this->secretKey = InMemory::plainText($secretKey);
        $this->issuer = $issuer;
        $this->signer = new Sha256();
        $this->encoder = new JoseEncoder();
    }

    public function generateToken(array $claims = []): string
    {
        $now = new \DateTimeImmutable();
        $expiry = $now->modify("+6 hours");

        $builder = (new Builder($this->encoder, new ChainedFormatter()))
            ->issuedBy($this->issuer)
            ->permittedFor($this->issuer)
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now) // Le token est utilisable immédiatement
            ->expiresAt($expiry); // Expire dans 6 heures

        // Ajouter les claims personnalisés
        foreach ($claims as $key => $value) {
            // S'assurer que les valeurs sont des types scalaires
            if (is_array($value) || is_object($value)) {
                throw new \InvalidArgumentException("Le claim '$key' doit être un type scalaire (string, int, float, bool)");
            }
            $builder = $builder->withClaim($key, $value);
        }

        return $builder->getToken($this->signer, $this->secretKey)->toString();
    }


    public function validateToken(string $tokenString): ?array
    {
        try {
            // Vérifier que le token n'est pas vide
            if (empty($tokenString)) {
                return null;
            }

            /** @var Plain $token */
            $token = (new Parser($this->encoder))->parse($tokenString);

            $validator = new Validator();
            
            // Vérifier la signature
            if (!$validator->validate($token, new SignedWith($this->signer, $this->secretKey))) {
                return null;
            }

            // Vérifier l'expiration avec l'API v4.0.x
            if ($token->isExpired(new \DateTimeImmutable())) {
                return null;
            }

            // Retourner les claims du token avec l'API v4.0.x
            $claims = [];
            foreach ($token->claims()->all() as $name => $value) {
                $claims[$name] = $value;
            }

            return $claims;
        } catch (\Exception $e) {
            // Log l'erreur pour debug
            error_log('Token validation error: ' . $e->getMessage());
            return null;
        }
    }

    
}
