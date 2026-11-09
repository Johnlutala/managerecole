<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use PHPUnit\Framework\Attributes\DataProvider;

final class ApiAuthenticationTest extends WebTestCase
{
    #[DataProvider('protectedEndpoints')]
    public function testMobileEndpointsRequireJwt(string $method, string $path): void
    {
        $client = static::createClient();
        $client->request($method, $path, server: ['CONTENT_TYPE' => 'application/json']);

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        self::assertJson($client->getResponse()->getContent());
        self::assertSame('1', json_decode($client->getResponse()->getContent(), true)['code']);
    }

    public static function protectedEndpoints(): iterable
    {
        yield 'student dossier' => ['GET', '/api/rest/v1/mobile/eleve/profil'];
        yield 'student schedule' => ['GET', '/api/rest/v1/mobile/eleve/emploi-du-temps'];
        yield 'student attendance' => ['GET', '/api/rest/v1/eleves/me/presences'];
        yield 'parent profile' => ['GET', '/api/rest/v1/mobile/parent/profil'];
        yield 'parent attendance' => ['GET', '/api/rest/v1/parent/presences'];
        yield 'teacher dashboard' => ['GET', '/api/rest/v1/mobile/professeur/tableau-de-bord'];
        yield 'teacher grades' => ['GET', '/api/rest/v1/mobile/professeur/cotations'];
        yield 'teacher grade submission' => ['POST', '/api/rest/v1/mobile/professeur/cotations'];
        yield 'lesson preparation list' => ['GET', '/api/rest/v1/mobile/professeur/preparations'];
        yield 'admin notes' => ['GET', '/api/rest/v1/mobile/admin/notes'];
        yield 'admin preparation validation' => ['GET', '/api/rest/v1/mobile/admin/preparations'];
        yield 'admin users' => ['GET', '/api/rest/v1/mobile/admin/utilisateurs'];
        yield 'admin dashboard' => ['GET', '/api/rest/v1/mobile/admin/tableau-de-bord'];
        yield 'admin annual results' => ['GET', '/api/rest/v1/mobile/admin/bulletins?ecole=1&anneeScolaire=1'];
        yield 'admin annual calculation' => ['POST', '/api/rest/v1/mobile/admin/bulletins/calculer'];
    }
}
