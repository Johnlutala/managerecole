<?php

namespace App\Controller\ApiResource;

use App\Entity\Eleve;
use App\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/rest/v1/eleves')]
final class EleveApiController extends AbstractApiController
{
    #[Route('/me/presences', name: 'api_eleve_my_presences', methods: ['GET'])]
    public function myPresences(Request $request): JsonResponse
    {
        $authentication = $this->checkAuthentication($request);
        if (!$authentication['status'])
            return $this->error($authentication['message'], [], 'Présences élève', $authentication['code']);

        $eleve = $authentication['user']->getEleve();
        if (!$this->userHasAnyRole($authentication['user'], ['ROLE_ELEVE']) || !$eleve)
            return $this->error(
                'Aucun profil élève associé à ce compte.',
                [],
                'Présences élève',
                Response::HTTP_FORBIDDEN
            );

        return $this->success($this->payload($eleve, true), 'Mes présences');
    }

    #[Route('/{id}', name: 'api_eleve_show', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function show(Request $request, Eleve $eleve): JsonResponse
    {
        $authentication = $this->checkAuthentication($request);
        if (!$authentication['status']) return $this->error(
            $authentication['message'],
            [],
            'Consultation élève',
            $authentication['code']
        );
        $user = $authentication['user'];
        $allowed = (
            $this->userHasAnyRole($user, ['ROLE_ELEVE'])
            && $user->getEleve()?->getId() === $eleve->getId()
        ) || (
            $this->userHasAnyRole($user, ['ROLE_PARENT'])
            && $user->getParent()?->getEleve()->contains($eleve)
        ) ||
            $this->isAdmin($user);

        if (!$allowed) return $this->error(
            'Vous ne pouvez consulter que les informations de votre enfant ou votre propre fiche.',
            [],
            'Consultation élève',
            Response::HTTP_FORBIDDEN
        );

        return $this->success($this->payload($eleve), 'Consultation élève', Response::HTTP_OK);
    }

    private function payload(Eleve $eleve, bool $onlyPresences = false): array
    {
        $presences = [];

        foreach ($eleve->getPresences() as $presence) if ($presence->getValidatedAt()) $presences[] = [
            'id' => $presence->getId(),
            'date' => $presence->getDate()?->format('Y-m-d'),
            'statut' => $presence->getStatut(),
            'heureArrivee' => $presence->getHeureArrivee()?->format('H:i'),
            'heureDepart' => $presence->getHeureDepart()?->format('H:i'),
            'observation' => $presence->getObservation()
        ];
        usort($presences, fn($a, $b) => strcmp($b['date'], $a['date']));
        if ($onlyPresences) return ['eleve' => [
            'id' => $eleve->getId(),
            'matricule' => $eleve->getMatricule(),
            'nom' => trim($eleve->getNom() . ' ' . $eleve->getPostnom() . ' ' . $eleve->getPrenom()),
            'classe' => $eleve->getClasse()?->getNom()
        ], 'presences' => $presences];

        return [
            'id' => $eleve->getId(),
            'matricule' => $eleve->getMatricule(),
            'nom' => $eleve->getNom(),
            'postnom' => $eleve->getPostnom(),
            'prenom' => $eleve->getPrenom(),
            'sexe' => $eleve->getSexe(),
            'dateNaissance' => $eleve->getDateNaissance()?->format('Y-m-d'),
            'lieuNaissance' => $eleve->getLieuNaissance(),
            'statut' => $eleve->isStatut(),
            'telephone' => $eleve->getPhone(),
            'email' => $eleve->getEmail(),
            'adresse' => $eleve->getAdresse(),
            'dateInscription' => $eleve->getDateInscription()?->format('Y-m-d'),
            'option' => $eleve->getOption()?->getNom(),
            'ecole' => $eleve->getEcole() ? [
                'id' => $eleve->getEcole()->getId(),
                'nom' => $eleve->getEcole()->getNom(),
                'adresse' => $eleve->getEcole()->getAdresse(),
                'telephone' => $eleve->getEcole()->getPhone(),
                'email' => $eleve->getEcole()->getEmail(),
            ] : null,
            'classe' => $eleve->getClasse() ? [
                'id' => $eleve->getClasse()->getId(),
                'nom' => $eleve->getClasse()->getNom(),
                'section' => $eleve->getClasse()->getSection()?->getNom(),
            ] : null,
            'notes' => array_values(array_map(
                static fn ($note): array => [
                    'id' => $note->getId(),
                    'cote' => $note->getCote(),
                    'observation' => $note->getObservation(),
                    'statut' => $note->getStatut(),
                    'cours' => $note->getEvaluation()?->getCours()?->getNom(),
                    'evaluation' => $note->getEvaluation()?->getNom(),
                    'bareme' => $note->getEvaluation()?->getBareme(),
                ],
                array_filter(
                    $eleve->getNotes()->toArray(),
                    static fn ($note): bool => $note->getStatut() === 'validee'
                )
            )),
            'presences' => $presences
        ];
    }

    private function isAdmin(User $user): bool
    {
        return (bool) array_intersect(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'], $user->getRoles());
    }
}
