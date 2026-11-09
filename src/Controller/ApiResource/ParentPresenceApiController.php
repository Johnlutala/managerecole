<?php

namespace App\Controller\ApiResource;

use App\Entity\Parents;
use App\Entity\Presence;
use App\Repository\PresenceRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/rest/v1/parent')]
final class ParentPresenceApiController extends AbstractApiController
{
    #[Route('/presences', name: 'api_parent_presences', methods: ['GET'])]
    public function index(
        Request $request,
        PresenceRepository $presences
    ): JsonResponse {
        $authentication = $this->checkAuthentication($request);

        if (!$authentication['status'])
            return $this->error(
                $authentication['message'],
                [],
                'Rapport parent',
                $authentication['code']
            );
        $parent = $this->em->getRepository(Parents::class)
            ->findOneBy(['user' => $authentication['user']]);

        if (!$this->userHasAnyRole($authentication['user'], ['ROLE_PARENT']) || !$parent)
            return $this->error(
                'Aucun profil parent associé à ce compte.',
                [],
                'Rapport parent',
                Response::HTTP_FORBIDDEN
            );

        $fromValue = $request->query->get('from', (new \DateTimeImmutable('first day of this month'))->format('Y-m-d'));
        $toValue = $request->query->get('to', (new \DateTimeImmutable('today'))->format('Y-m-d'));
        $from = is_string($fromValue) ? \DateTime::createFromFormat('!Y-m-d', $fromValue) : false;
        $to = is_string($toValue) ? \DateTime::createFromFormat('!Y-m-d', $toValue) : false;
        $fromErrors = \DateTime::getLastErrors();
        $toErrors = \DateTime::getLastErrors();
        if (
            $from === false
            || $to === false
            || ($fromErrors !== false && ($fromErrors['warning_count'] > 0 || $fromErrors['error_count'] > 0))
            || ($toErrors !== false && ($toErrors['warning_count'] > 0 || $toErrors['error_count'] > 0))
            || $from > $to
            || $from->diff($to)->days > 366
        ) {
            return $this->error(
                'La période doit utiliser le format AAAA-MM-JJ, être cohérente et ne pas dépasser un an.',
                [],
                'Rapport parent',
                Response::HTTP_BAD_REQUEST
            );
        }
        $to->setTime(23, 59, 59);
        $children = [];
        foreach ($parent->getEleve() as $eleve) {
            $items = $presences->createQueryBuilder('p')
                ->andWhere('p.eleve = :eleve')
                ->andWhere('p.date BETWEEN :from AND :to')
                ->andWhere('p.validatedAt IS NOT NULL')
                ->setParameter('eleve', $eleve)
                ->setParameter('from', $from)
                ->setParameter('to', $to)
                ->orderBy('p.date', 'DESC')
                ->getQuery()
                ->getResult();

            $children[] = [
                'id' => $eleve->getId(),
                'matricule' => $eleve->getMatricule(),
                'nom' => trim($eleve->getNom() . ' ' . $eleve->getPostnom() . ' ' . $eleve->getPrenom()),
                'classe' => $eleve->getClasse()?->getNom(),
                'presences' => array_map(static fn(Presence $p) => [
                    'date' => $p->getDate()?->format('Y-m-d'),
                    'statut' => $p->getStatut(),
                    'heureArrivee' => $p->getHeureArrivee()?->format('H:i'),
                    'heureDepart' => $p->getHeureDepart()?->format('H:i'),
                    'observation' => $p->getObservation()
                ], $items)
            ];
        }

        return $this->success(
            [
                'periode' => [
                    'du' => $from->format('Y-m-d'),
                    'au' => $to->format('Y-m-d')
                ],
                'enfants' => $children
            ],
            'Rapport de présences parent'
        );
    }
}
