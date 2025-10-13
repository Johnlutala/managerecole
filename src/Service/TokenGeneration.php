<?php 

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Validator;

class TokenGeneration
{

    private string $secretKey;
    private string $issuer;
    private Sha256 $signer;

    public function __construct(string $secretKey, string $issuer = 'flexroll-workshop-api')
    {
        $this->secretKey = $secretKey;
        $this->issuer = $issuer;
        $this->signer = new Sha256();
    }

    public function generateToken(array $claims = []): string
    {
        $now = new \DateTimeImmutable();
        
        $builder = (new Builder())
            ->issuedBy($this->issuer)
            ->permittedFor($this->issuer)
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now->modify('+6 hours'))
            ->expiresAt($now->modify("+6 hours"));

        // Ajouter les claims personnalisés
        foreach ($claims as $key => $value) {
            $builder = $builder->withClaim($key, $value);
        }

        return (string) $builder->getToken($this->signer, new Key($this->secretKey));
    }


    public function validateToken(string $tokenString): ?Token
    {
        try {
            $token = (new Parser())->parse($tokenString);

            $validator = new Validator();
            
            // Vérifier la signature
            if (!$validator->validate($token, new SignedWith($this->signer, new Key($this->secretKey)))) {
                return null;
            }

            // Vérifier l'expiration
            if ($token->isExpired(new \DateTimeImmutable())) {
                return null;
            }

            return $token;
        } catch (\Exception $e) {
            return null;
        }
    }

    
}
