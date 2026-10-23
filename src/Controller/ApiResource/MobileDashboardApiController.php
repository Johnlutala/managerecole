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
        $auth=$this->checkAuthentication($request); if(!$auth['status'])return $this->error($auth['message'],[],'Profil mobile',$auth['code']); $user=$auth['user'];
        return $this->success(['id'=>$user->getId(),'nom'=>trim($user->getLastname().' '.$user->getFirstname()),'email'=>$user->getEmail(),'roles'=>$user->getRoles(),'espace'=>$this->space($user),'profil'=>$this->profile($user)],'Profil mobile');
    }
    #[Route('/parent/enfants', name: 'api_mobile_parent_children', methods: ['GET'])]
    public function parentChildren(Request $request): JsonResponse
    {
        $auth=$this->checkAuthentication($request); if(!$auth['status'])return $this->error($auth['message'],[],'Enfants du parent',$auth['code']); $parent=$auth['user']->getParent(); if(!$parent)return $this->error('Cet espace est réservé aux comptes parents.',[],'Enfants du parent',Response::HTTP_FORBIDDEN);
        return $this->success(['parent'=>trim($parent->getNom().' '.$parent->getPostnom().' '.$parent->getPrenom()),'nombreEnfants'=>$parent->getEleve()->count(),'enfants'=>array_map(fn(Eleve $e)=>$this->child($e),$parent->getEleve()->toArray())],'Enfants du parent');
    }
    #[Route('/eleve/emploi-du-temps', name: 'api_mobile_student_schedule', methods: ['GET'])]
    public function studentSchedule(Request $request, CreneauHoraireRepository $slots): JsonResponse
    {
        $auth=$this->checkAuthentication($request);if(!$auth['status'])return $this->error($auth['message'],[],'Emploi du temps élève',$auth['code']);$eleve=$auth['user']->getEleve();if(!$eleve)return $this->error('Cet espace est réservé aux comptes élèves.',[],'Emploi du temps élève',Response::HTTP_FORBIDDEN);
        $items=$slots->createQueryBuilder('c')->join('c.horaire','h')->andWhere('h.classe=:classe')->setParameter('classe',$eleve->getClasse())->orderBy('c.jour','ASC')->addOrderBy('c.heure','ASC')->getQuery()->getResult(); return $this->success(['eleve'=>$this->child($eleve),'creneaux'=>array_map(fn(CreneauHoraire $c)=>$this->slot($c),$items)],'Emploi du temps élève');
    }
    #[Route('/professeur/tableau-de-bord', name: 'api_mobile_teacher_dashboard', methods: ['GET'])]
    public function teacherDashboard(Request $request, CreneauHoraireRepository $slots): JsonResponse
    {
        $auth=$this->checkAuthentication($request);if(!$auth['status'])return $this->error($auth['message'],[],'Tableau professeur',$auth['code']);$prof=$auth['user']->getProfesseur();if(!$prof)return $this->error('Cet espace est réservé aux comptes professeurs.',[],'Tableau professeur',Response::HTTP_FORBIDDEN);
        $items=$slots->findBy(['professeur'=>$prof],['id'=>'ASC']); return $this->success(['professeur'=>['id'=>$prof->getId(),'matricule'=>$prof->getMatricule(),'nom'=>trim($prof->getNom().' '.$prof->getPostnom().' '.$prof->getPrenom()),'ecole'=>$prof->getEcole()?->getNom(),'section'=>$prof->getSection()?->getNom(),'specialite'=>$prof->getSpecialite()],'chargeHoraire'=>array_map(fn(CreneauHoraire $c)=>$this->slot($c),$items)],'Tableau professeur');
    }
    #[Route('/admin/tableau-de-bord', name: 'api_mobile_admin_dashboard', methods: ['GET'])]
    public function adminDashboard(Request $request, EleveRepository $eleves, ProfesseurRepository $professeurs, ClasseRepository $classes, PresenceRepository $presences): JsonResponse
    {
        $auth=$this->checkAuthentication($request);if(!$auth['status'])return $this->error($auth['message'],[],'Tableau admin',$auth['code']);if(!array_intersect(['ROLE_ADMIN','ROLE_SUPER_ADMIN'],$auth['user']->getRoles()))return $this->error('Cet espace est réservé aux administrateurs.',[],'Tableau admin',Response::HTTP_FORBIDDEN);$today=new \DateTime('today');return $this->success(['elevesActifs'=>$eleves->count(['statut'=>true,'deleted'=>false]),'professeursActifs'=>$professeurs->count(['deleted'=>false]),'classesActives'=>$classes->count(['deleted'=>false]),'presencesElevesAujourdhui'=>$presences->count(['date'=>$today,'deleted'=>false]),'presencesValideesAujourdhui'=>$presences->createQueryBuilder('p')->select('COUNT(p.id)')->andWhere('p.date=:date')->andWhere('p.validatedAt IS NOT NULL')->setParameter('date',$today)->getQuery()->getSingleScalarResult()],'Tableau admin');
    }
    private function space(User $u):string{if($u->getEleve())return'eleve';if($u->getParent())return'parent';if($u->getProfesseur())return'professeur';if(array_intersect(['ROLE_ADMIN','ROLE_SUPER_ADMIN'],$u->getRoles()))return'admin';return'personnel';}
    private function profile(User $u):array{return $u->getEleve()? $this->child($u->getEleve()):($u->getParent()?['id'=>$u->getParent()->getId(),'telephone'=>$u->getParent()->getTelephone(),'nombreEnfants'=>$u->getParent()->getEleve()->count()]:($u->getProfesseur()?['id'=>$u->getProfesseur()->getId(),'matricule'=>$u->getProfesseur()->getMatricule(),'ecole'=>$u->getProfesseur()->getEcole()?->getNom()]:[]));}
    private function child(Eleve $e):array{return['id'=>$e->getId(),'matricule'=>$e->getMatricule(),'nom'=>trim($e->getNom().' '.$e->getPostnom().' '.$e->getPrenom()),'classe'=>$e->getClasse()?->getNom(),'ecole'=>$e->getEcole()?->getNom()];}
    private function slot(CreneauHoraire $c):array{return['id'=>$c->getId(),'jour'=>$c->getJour()?->getNom(),'ordreJour'=>$c->getJour()?->getOrdre(),'heureDebut'=>$c->getHeure()?->getHeureDebut()?->format('H:i'),'heureFin'=>$c->getHeure()?->getHeureFin()?->format('H:i'),'cours'=>$c->getCours()?->getNom(),'classe'=>$c->getHoraire()?->getClasse()?->getNom(),'statut'=>$c->getStatut(),'observation'=>$c->getObservation()];}
}
