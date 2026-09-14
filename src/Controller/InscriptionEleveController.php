<?php

namespace App\Controller;

use App\Entity\Eleve;
use App\Entity\InscriptionEleve;
use App\Entity\Parents;
use App\Entity\User;
use App\Form\InscriptionEleveType;
use App\Repository\EleveRepository;
use App\Repository\InscriptionEleveRepository;
use App\Repository\ParentsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/inscription/eleve', name: 'app_inscription_eleve_')]
final class InscriptionEleveController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request, InscriptionEleveRepository $repository, EntityManagerInterface $entityManager): Response
    {
        $inscription = new InscriptionEleve();
        $form = $this->createForm(InscriptionEleveType::class, $inscription);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $inscription->setStatut('en_attente');
            if ($this->getUser() instanceof User) {
                $inscription->setCreatedBy($this->getUser());
            }
            $entityManager->persist($inscription);
            $entityManager->flush();
            $this->addFlash('success', 'La demande d’inscription a été enregistrée et attend validation.');
            return $this->redirectToRoute('app_inscription_eleve_index');
        }
        return $this->render('inscription_eleve/index.html.twig', ['inscription_eleves' => $repository->findBy([], ['id' => 'DESC']), 'form' => $form->createView()]);
    }

    #[Route('/lookup', name: 'lookup', methods: ['GET'])]
    public function lookup(Request $request, EleveRepository $eleves): JsonResponse
    {
        $reference = trim((string) $request->query->get('reference', ''));
        if ($reference === '') {
            return $this->json(['found' => false]);
        }
        $eleve = $eleves->findOneBy(['matricule' => $reference]);
        if (!$eleve) {
            return $this->json(['found' => false]);
        }
        return $this->json(['found' => true, 'eleve' => ['matricule' => $eleve->getMatricule(), 'nom' => $eleve->getNom(), 'postnom' => $eleve->getPostnom(), 'prenom' => $eleve->getPrenom(), 'sexe' => $eleve->getSexe(), 'dateNaissance' => $eleve->getDateNaissance()?->format('Y-m-d'), 'lieuNaissance' => $eleve->getLieuNaissance(), 'adresse' => $eleve->getAdresse(), 'telephone' => $eleve->getPhone()]]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $inscription = new InscriptionEleve();
        $form = $this->createForm(InscriptionEleveType::class, $inscription);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $inscription->setStatut('en_attente');
            $entityManager->persist($inscription);
            $entityManager->flush();
            return $this->redirectToRoute('app_inscription_eleve_index');
        }
        return $this->render('inscription_eleve/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}/validate', name: 'validate', methods: ['POST'])]
    public function validate(Request $request, InscriptionEleve $inscription, EleveRepository $eleves, ParentsRepository $parents, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('validate' . $inscription->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }
        if ($inscription->getStatut() === 'validee') {
            $this->addFlash('info', 'Cette inscription est déjà validée.');
            return $this->redirectToRoute('app_inscription_eleve_index');
        }
        $eleve = $inscription->getReference() ? $eleves->findOneBy(['matricule' => $inscription->getReference()]) : null;
        if (!$eleve) {
            $eleve = (new Eleve())->setNom($inscription->getNom())->setPostnom($inscription->getPostnom())->setPrenom($inscription->getPrenom() ?? $inscription->getNom())->setSexe($inscription->getSexe())->setDateNaissance($inscription->getDateNaissance())->setLieuNaissance($inscription->getLieuNaissance())->setAdresse($inscription->getAdresse())->setPhone($inscription->getTelephone())->setDateInscription(new \DateTime())->setStatut(true);
            $entityManager->persist($eleve);
        }
        $eleve->setEcole($inscription->getEtablissement())->setClasse($inscription->getClasse());
        $parent = $inscription->getTelephoneParent() ? $parents->findOneBy(['telephone' => $inscription->getTelephoneParent()]) : null;
        if (!$parent) {
            $parent = (new Parents())->setNom($inscription->getNomParent())->setPostnom($inscription->getPostnomParent())->setPrenom($inscription->getPrenomParent())->setTelephone($inscription->getTelephoneParent() ?? 'Non renseigné')->setAdresse($inscription->getAdresseParent() ?? 'Non renseignée')->setProfession($inscription->getProfessionParent())->setType($inscription->getLienParental());
            $entityManager->persist($parent);
        }
        $eleve->addParent($parent);
        $inscription->setStatut('validee')->setDateValidation(new \DateTime());
        $entityManager->flush();
        $this->addFlash('success', 'Inscription validée : l’élève et son parent ont été enregistrés.');
        return $this->redirectToRoute('app_inscription_eleve_index');
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(InscriptionEleve $inscriptionEleve): Response
    {
        return $this->render('inscription_eleve/show.html.twig', ['inscription_eleve' => $inscriptionEleve]);
    }
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, InscriptionEleve $inscriptionEleve, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(InscriptionEleveType::class, $inscriptionEleve);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_inscription_eleve_index');
        }
        return $this->render('inscription_eleve/edit.html.twig', ['inscription_eleve' => $inscriptionEleve, 'form' => $form]);
    }
    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, InscriptionEleve $inscriptionEleve, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $inscriptionEleve->getId(), $request->request->get('_token'))) {
            $entityManager->remove($inscriptionEleve);
            $entityManager->flush();
        }
        return $this->redirectToRoute('app_inscription_eleve_index');
    }
}
