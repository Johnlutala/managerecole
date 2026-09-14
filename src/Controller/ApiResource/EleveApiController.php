<?php

namespace App\Controller\ApiResource;

use App\Entity\Eleve;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/rest/v1/eleves')]
final class EleveApiController extends AbstractApiController
{
    #[Route('/{id}', name: 'api_eleve_show', methods: ['GET'])]
    public function show(Request $request, Eleve $eleve): JsonResponse
    {
        $authentication = $this->checkAuthentication($request);
        if (!$authentication['status']) {
            return $this->error(
                $authentication['message'],
                [],
                'Consultation élève',
                $authentication['code']
            );
        }

        return $this->success([
            'id' => $eleve->getId(),
            'matricule' => $eleve->getMatricule(),
            'nom' => $eleve->getNom(),
            'postnom' => $eleve->getPostnom(),
            'prenom' => $eleve->getPrenom(),
            'sexe' => $eleve->getSexe(),
            'dateNaissance' => $eleve->getDateNaissance()?->format('Y-m-d'),
            'lieuNaissance' => $eleve->getLieuNaissance(),
            'statut' => $eleve->isStatut(),
            'ecole' => $eleve->getEcole()?->getNom(),
            'classe' => $eleve->getClasse()?->getNom(),
            'notes' => array_map(static fn($note) => [
                'id' => $note->getId(),
                'cote' => $note->getCote(),
                'observation' => $note->getObservation(),
            ], $eleve->getNotes()->toArray()),
            'presences' => array_map(static fn($presence) => [
                'id' => $presence->getId(),
                'date' => $presence->getDate()?->format('Y-m-d'),
                'statut' => $presence->getStatut(),
                'heureArrivee' => $presence->getHeureArrivee()?->format('H:i:s'),
                'heureDepart' => $presence->getHeureDepart()?->format('H:i:s'),
            ], $eleve->getPresences()->toArray()),
        ], 'Consultation élève', Response::HTTP_OK);
    }
}
