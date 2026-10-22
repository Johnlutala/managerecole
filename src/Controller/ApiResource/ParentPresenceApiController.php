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

        if (!$parent)
            return $this->error(
                'Aucun profil parent associé à ce compte.',
                [],
                'Rapport parent',
                Response::HTTP_FORBIDDEN
            );

        $from = new \DateTime($request->query->get('from', 'first day of this month'));
        $to = new \DateTime($request->query->get('to', 'today'));
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
