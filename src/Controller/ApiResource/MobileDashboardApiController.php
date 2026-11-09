<?php

namespace App\Controller\ApiResource;

use App\Entity\Classe;
use App\Entity\CreneauHoraire;
use App\Entity\Eleve;
use App\Entity\Presence;
use App\Entity\Professeur;
use App\Entity\User;
use App\Repository\ClasseRepository;
use App\Repository\CreneauHoraireRepository;
use App\Repository\EleveRepository;
use App\Repository\PresenceRepository;
use App\Repository\ProfesseurRepository;
use App\Repository\NoteRepository;
use App\Repository\PreparationCoursRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/rest/v1/mobile')]
final class MobileDashboardApiController extends AbstractApiController
{
    #[Route('/me', name: 'api_mobile_me', methods: ['GET'])]
    public function me(Request $request): JsonResponse
    {
        $auth = $this->checkAuthentication($request);

        if (!$auth['status'])
            return $this->error(
                $auth['message'],
                [],
                'Profil mobile',
                $auth['code']
            );

        $user = $auth['user'];

        return $this->success([
            'id' => $user->getId(),
            'nom' => trim($user->getLastname() . ' ' . $user->getFirstname()),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'espace' => $this->space($user),
            'profil' => $this->profile($user),
        ], 'Profil mobile');
    }

    #[Route('/parent/enfants', name: 'api_mobile_parent_children', methods: ['GET'])]
    public function parentChildren(Request $request): JsonResponse
    {
        $auth = $this->checkAuthentication($request);
        if (!$auth['status'])
            return $this->error(
                $auth['message'],
                [],
                'Enfants du parent',
                $auth['code']
            );

        $parent = $auth['user']->getParent();

        if (!$this->userHasAnyRole($auth['user'], ['ROLE_PARENT']) || !$parent)
            return $this->error(
                'Cet espace est réservé aux comptes parents.',
                [],
                'Enfants du parent',
                Response::HTTP_FORBIDDEN
            );
        return $this->success([
            'parent' => [
                'id' => $parent->getId(),
                'nom' => $parent->getNom(),
                'postnom' => $parent->getPostnom(),
                'prenom' => $parent->getPrenom(),
                'telephone' => $parent->getTelephone(),
                'email' => $parent->getEmail(),
                'adresse' => $parent->getAdresse(),
                'profession' => $parent->getProfession(),
                'type' => $parent->getType(),
            ],
            'nombreEnfants' => $parent->getEleve()->count(),
            'enfants' => array_map(fn(Eleve $e) => $this->child($e), $parent->getEleve()->toArray())
        ], 'Enfants du parent');
    }


    #[Route('/eleve/emploi-du-temps', name: 'api_mobile_student_schedule', methods: ['GET'])]
    public function studentSchedule(
        Request $request,
        CreneauHoraireRepository $slots
    ): JsonResponse {
        $auth = $this->checkAuthentication($request);

        if (!$auth['status']) {
            return $this->error(
                $auth['message'],
                [],
                'Emploi du temps élève',
                $auth['code']
            );
        }

        $eleve = $auth['user']->getEleve();

        if (!$this->userHasAnyRole($auth['user'], ['ROLE_ELEVE']) || !$eleve) {
            return $this->error(
                'Cet espace est réservé aux comptes élèves.',
                [],
                'Emploi du temps élève',
                Response::HTTP_FORBIDDEN
            );
        }

        $classe = $eleve->getClasse();
        $ecole = $eleve->getEcole();
        $option = $eleve->getOption();

        if (!$classe || !$ecole) {
            return $this->error(
                'La classe ou l’école de cet élève n’est pas renseignée.',
                [],
                'Emploi du temps élève',
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $qb = $slots->createQueryBuilder('c')
            ->join('c.horaire', 'h')
            ->leftJoin('c.jour', 'j')
            ->leftJoin('c.heure', 'he')
            ->leftJoin('c.cours', 'co')
            ->leftJoin('c.professeur', 'p')
            ->andWhere('h.classe = :classe')
            ->andWhere('h.ecole = :ecole')
            ->setParameter('classe', $classe)
            ->setParameter('ecole', $ecole);

        if ($option !== null) {
            // Élève d'une option : récupérer uniquement son horaire.
            $qb->andWhere('h.option = :option')
                ->setParameter('option', $option);
        } else {

            // Élève sans option : récupérer uniquement l'horaire
            // général de sa classe, sans horaire d'option.
            $qb->andWhere('h.option IS NULL');
        }

        $items = $qb
            ->orderBy('j.ordre', 'ASC')
            ->addOrderBy('he.id', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->success(
            [
                'eleve' => $this->child($eleve),
                'option' => $option?->getNom(),
                'creneaux' => array_map(
                    fn(CreneauHoraire $c) => $this->slot($c),
                    $items
                ),
            ],
            'Emploi du temps élève'
        );
    }


    #[Route('/professeur/tableau-de-bord', name: 'api_mobile_teacher_dashboard', methods: ['GET'])]
    public function teacherDashboard(
        Request $request,
        CreneauHoraireRepository $slots
    ): JsonResponse {
        $auth = $this->checkAuthentication($request);
        if (!$auth['status'])
            return $this->error(
                $auth['message'],
                [],
                'Tableau professeur',
                $auth['code']
            );
        $prof = $auth['user']->getProfesseur();

        if (!$this->userHasAnyRole($auth['user'], ['ROLE_PROFESSEUR']) || !$prof)
            return $this->error(
                'Cet espace est réservé aux comptes professeurs.',
                [],
                'Tableau professeur',
                Response::HTTP_FORBIDDEN
            );

        $items = $slots->findBy(['professeur' => $prof], ['id' => 'ASC']);
        return $this->success(
            [
                'professeur' => [
                    'id' => $prof->getId(),
                    'matricule' => $prof->getMatricule(),
                    'nom' => $prof->getNom(),
                    'postnom' => $prof->getPostnom(),
                    'prenom' => $prof->getPrenom(),
                    'sexe' => $prof->getSexe(),
                    'telephone' => $prof->getPhone(),
                    'email' => $prof->getEmail(),
                    'adresse' => $prof->getAdresse(),
                    'dateEmbauche' => $prof->getDateEmbauche()?->format('Y-m-d'),
                    'grade' => $prof->getGrade(),
                    'statut' => $prof->getStatut(),
                    'specialite' => $prof->getSpecialite(),
                    'ecole' => $prof->getEcole() ? [
                        'id' => $prof->getEcole()->getId(),
                        'nom' => $prof->getEcole()->getNom(),
                        'adresse' => $prof->getEcole()->getAdresse(),
                        'telephone' => $prof->getEcole()->getPhone(),
                        'email' => $prof->getEcole()->getEmail(),
                    ] : null,
                    'section' => $prof->getSection()?->getNom(),
                    'anneeScolaire' => $prof->getAnneeScolaire()?->getLibelle(),
                ],
                'chargeHoraire' => array_map(fn(CreneauHoraire $c) => $this->slot($c), $items)
            ],
            'Tableau professeur'
        );
    }

    #[Route('/admin/tableau-de-bord', name: 'api_mobile_admin_dashboard', methods: ['GET'])]
    public function adminDashboard(
        Request $request,
        EleveRepository $eleves,
        ProfesseurRepository $professeurs,
        ClasseRepository $classes,
        PresenceRepository $presences,
        NoteRepository $notes,
        PreparationCoursRepository $preparations,
        UserRepository $users
    ): JsonResponse {

        $auth = $this->checkAuthentication($request);

        if (!$auth['status'])
            return $this->error(
                $auth['message'],
                [],
                'Tableau admin',
                $auth['code']
            );

        if (!array_intersect([
            'ROLE_ADMIN',
            'ROLE_SUPER_ADMIN'
        ], $auth['user']->getRoles()))
            return $this->error(
                'Cet espace est réservé aux administrateurs.',
                [],
                'Tableau admin',
                Response::HTTP_FORBIDDEN
            );

        $today = new \DateTime('today');

        return $this->success(
            [
                'elevesActifs' => $eleves->count(['statut' => true, 'deleted' => false]),
                'professeursActifs' => $professeurs->count(['deleted' => false]),
                'classesActives' => $classes->count(['deleted' => false]),
                'utilisateursActifs' => $users->count(['enabled' => true, 'deleted' => false]),
                'notesAValider' => $notes->countByStatus('soumise'),
                'preparationsAValider' => $preparations->countSoumises(),
                'presencesElevesAujourdhui' => $presences->count(['date' => $today, 'deleted' => false]),
                'presencesValideesAujourdhui' => $presences->createQueryBuilder('p')
                    ->select('COUNT(p.id)')
                    ->andWhere('p.date=:date')
                    ->andWhere('p.validatedAt IS NOT NULL')
                    ->setParameter('date', $today)
                    ->getQuery()
                    ->getSingleScalarResult()
            ],
            'Tableau admin'
        );
    }

    private function space(User $u): string
    {

        if ($u->getEleve())
            return 'eleve';
        if ($u->getParent())
            return 'parent';
        if ($u->getProfesseur())
            return 'professeur';
        if (array_intersect(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'], $u->getRoles()))
            return 'admin';
        return 'personnel';
    }

    private function profile(User $u): array
    {
        return $u->getEleve() ?
            $this->child($u->getEleve()) : ($u->getParent() ? [
                'id' => $u->getParent()->getId(),
                'nom' => $u->getParent()->getNom(),
                'postnom' => $u->getParent()->getPostnom(),
                'prenom' => $u->getParent()->getPrenom(),
                'telephone' => $u->getParent()->getTelephone(),
                'email' => $u->getParent()->getEmail(),
                'adresse' => $u->getParent()->getAdresse(),
                'profession' => $u->getParent()->getProfession(),
                'type' => $u->getParent()->getType(),
                'nombreEnfants' => $u->getParent()->getEleve()->count()
            ] : ($u->getProfesseur() ? [
                'id' => $u->getProfesseur()->getId(),
                'matricule' => $u->getProfesseur()->getMatricule(),
                'nom' => $u->getProfesseur()->getNom(),
                'postnom' => $u->getProfesseur()->getPostnom(),
                'prenom' => $u->getProfesseur()->getPrenom(),
                'sexe' => $u->getProfesseur()->getSexe(),
                'dateNaissance' => $u->getProfesseur()->getDateNaissance()?->format('Y-m-d'),
                'telephone' => $u->getProfesseur()->getPhone(),
                'email' => $u->getProfesseur()->getEmail(),
                'adresse' => $u->getProfesseur()->getAdresse(),
                'dateEmbauche' => $u->getProfesseur()->getDateEmbauche()?->format('Y-m-d'),
                'specialite' => $u->getProfesseur()->getSpecialite(),
                'grade' => $u->getProfesseur()->getGrade(),
                'statut' => $u->getProfesseur()->getStatut(),
                'ecole' => $u->getProfesseur()->getEcole() ? [
                    'id' => $u->getProfesseur()->getEcole()->getId(),
                    'nom' => $u->getProfesseur()->getEcole()->getNom(),
                    'adresse' => $u->getProfesseur()->getEcole()->getAdresse(),
                    'telephone' => $u->getProfesseur()->getEcole()->getPhone(),
                    'email' => $u->getProfesseur()->getEcole()->getEmail(),
                ] : null,
                'section' => $u->getProfesseur()->getSection()?->getNom(),
                'anneeScolaire' => $u->getProfesseur()->getAnneeScolaire()?->getLibelle(),
            ] : []));
    }

    private function child(Eleve $e): array
    {
        return [
            'id' => $e->getId(),
            'matricule' => $e->getMatricule(),
            'nom' => trim($e->getNom() . ' ' . $e->getPostnom() . ' ' . $e->getPrenom()),
            'classe' => $e->getClasse()?->getNom(),
            'option' => $e->getOption()?->getNom(),
            'sexe' => $e->getSexe(),
            'dateNaissance' => $e->getDateNaissance()?->format('Y-m-d'),
            'lieuNaissance' => $e->getLieuNaissance(),
            'telephone' => $e->getPhone(),
            'email' => $e->getEmail(),
            'ecole' => $e->getEcole() ? [
                'id' => $e->getEcole()->getId(),
                'nom' => $e->getEcole()->getNom(),
                'adresse' => $e->getEcole()->getAdresse(),
                'telephone' => $e->getEcole()->getPhone(),
                'email' => $e->getEcole()->getEmail(),
            ] : null
        ];
    }


    private function slot(CreneauHoraire $c): array
    {
        $professeur = $c->getProfesseur();

        return [
            'id' => $c->getId(),

            'jour' => $c->getJour()?->getNom(),
            'ordreJour' => $c->getJour()?->getOrdre(),

            'heureDebut' => $c->getHeure()?->getHeureDebut()?->format('H:i'),
            'heureFin' => $c->getHeure()?->getHeureFin()?->format('H:i'),

            'cours' => $c->getCours()?->getNom(),

            'professeur' => $professeur ? [
                'id' => $professeur->getId(),
                'matricule' => $professeur->getMatricule(),
                'nom' => trim(
                    $professeur->getNom() . ' ' .
                        $professeur->getPostnom() . ' ' .
                        $professeur->getPrenom()
                ),
                'specialite' => $professeur->getSpecialite(),
            ] : null,

            'classe' => $c->getHoraire()?->getClasse()?->getNom(),

            'statut' => $c->getStatut(),
            'observation' => $c->getObservation(),
        ];
    }
}
