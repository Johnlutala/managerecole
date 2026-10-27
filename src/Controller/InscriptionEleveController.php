<?php

namespace App\Controller;

use App\Entity\Eleve;
use App\Entity\InscriptionEleve;
use App\Entity\Parents;
use App\Entity\User;
use App\Entity\Ecole;
use App\Entity\AnneeScolaire;
use App\Entity\Section;
use App\Entity\Classe;
use App\Entity\Option;
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

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        InscriptionEleveRepository $repository,
        EntityManagerInterface $entityManager
    ): Response {
        $inscription = new InscriptionEleve();
        $form = $this->createForm(InscriptionEleveType::class, $inscription);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $inscription = $form->getData();

            $inscription->setStatut('en_attente');

            if ($this->getUser() instanceof User) {
                $inscription->setCreatedBy($this->getUser());
            }
            $entityManager->persist($inscription);
            $entityManager->flush();
            $this->addFlash(
                'success',
                'La demande d’inscription a été enregistrée et attend validation.'
            );
            return $this->redirectToRoute('app_inscription_eleve_index');
        }
        return $this->render('inscription_eleve/index.html.twig', [
            'inscription_eleves' => $repository->findBy(
                [],
                ['id' => 'DESC']
            ),
            'form' => $form->createView()
        ]);
    }
     #[Route('/verifier-email', name: 'verifier_email', methods: ['GET'])]    
    public function verifierEmail(
        Request $request,
        InscriptionEleveRepository $inscriptionRepository
    ): JsonResponse {
        $email = trim((string) $request->query->get('email', ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'valid' => false,
                'available' => false,
                'message' => 'Veuillez saisir une adresse e-mail valide.',
            ]);
        }

        $inscriptionExistante = $inscriptionRepository->findOneBy([
            'email' => $email,
        ]);

        return $this->json([
            'valid' => true,
            'available' => $inscriptionExistante === null,
            'message' => $inscriptionExistante
                ? 'Cette adresse e-mail est déjà utilisée.'
                : 'Adresse e-mail disponible.',
        ]);
    }

    #[Route('/lookup', name: 'lookup', methods: ['GET'])]
    public function lookup(
        Request $request,
        EleveRepository $eleves,
        InscriptionEleveRepository $inscriptions
    ): JsonResponse {
        $reference = trim((string) $request->query->get('reference', ''));

        if ($reference === '') {
            return $this->json(['found' => false]);
        }

        // Recherche de l'élève existant
        $eleve = $eleves->findOneBy([
            'matricule' => $reference
        ]);

        if (!$eleve) {
            return $this->json([
                'found' => false
            ]);
        }

        // Recherche de son ancienne inscription
        $inscription = $inscriptions->findOneBy([
            'reference' => $reference
        ]);

        return $this->json([
            'found' => true,

            'eleve' => [
                'matricule' => $eleve->getMatricule(),
                'nom' => $eleve->getNom(),
                'postnom' => $eleve->getPostnom(),
                'prenom' => $eleve->getPrenom(),
                'sexe' => $eleve->getSexe(),
                'dateNaissance' => $eleve->getDateNaissance()?->format('Y-m-d'),
                'lieuNaissance' => $eleve->getLieuNaissance(),
                'adresse' => $eleve->getAdresse(),
                'telephone' => $eleve->getPhone(),

                // Informations du parent
                'nomParent' => $inscription?->getNomParent(),
                'postnomParent' => $inscription?->getPostnomParent(),
                'prenomParent' => $inscription?->getPrenomParent(),
                'telephoneParent' => $inscription?->getTelephoneParent(),
                'emailParent' => $inscription?->getEmailParent(),
                'lienParental' => $inscription?->getLienParental(),
                'professionParent' => $inscription?->getProfessionParent(),
                'adresseParent' => $inscription?->getAdresseParent(),
            ]

        ]);
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
    public function validate(
        Request $request,
        InscriptionEleve $inscription,
        EleveRepository $eleves,
        ParentsRepository $parents,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid(
            'validate' . $inscription->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException();
        }

        if ($inscription->getStatut() === 'validee') {
            $this->addFlash('info', 'Cette inscription est déjà validée.');
            return $this->redirectToRoute('app_inscription_eleve_index');
        }

        // 1. Vérifier la présence de la classe.
        $classe = $inscription->getClasse();

        if (!$classe) {
            $this->addFlash(
                'danger',
                'Validation impossible : aucune classe n’est associée à cette inscription.'
            );

            return $this->redirectToRoute('app_inscription_eleve_index');
        }

        // 2. Vérifier que l'option appartient à la classe.
        $option = $inscription->getOptions();

        if (
            $option !== null
            && $option->getClasse()?->getId() !== $classe->getId()
        ) {
            $this->addFlash(
                'danger',
                'L’option sélectionnée ne correspond pas à la classe.'
            );

            return $this->redirectToRoute('app_inscription_eleve_index');
        }

        // 3. Retrouver l'élève existant ou le créer.
        $reference = trim((string) $inscription->getReference());

        $eleve = $reference !== ''
            ? $eleves->findOneBy(['matricule' => $reference])
            : null;

        if (!$eleve) {
            $eleve = new Eleve();
            $eleve->setDateInscription(new \DateTime());
            $eleve->setStatut(true);

            // Si une référence est fournie, on l'utilise comme matricule.
            if ($reference !== '') {
                $eleve->setMatricule($reference);
            }

            $entityManager->persist($eleve);
        }

        // 4. Transférer toutes les informations de l'inscription vers l'élève.
        $eleve
            ->setNom($inscription->getNom())
            ->setPostnom($inscription->getPostnom())
            ->setPrenom($inscription->getPrenom() ?? '')
            ->setSexe($inscription->getSexe())
            ->setDateNaissance($inscription->getDateNaissance())
            ->setLieuNaissance($inscription->getLieuNaissance())
            ->setAdresse($inscription->getAdresse())
            ->setPhone($inscription->getTelephone())
            ->setEmail($inscription->getEmail())
            ->setEcole($inscription->getEtablissement())
            ->setClasse($classe)
            ->setOption($option);

        // 5. Retrouver le parent par téléphone ou créer un parent.
        $telephoneParent = trim(
            (string) $inscription->getTelephoneParent()
        );

        $parent = $telephoneParent !== ''
            ? $parents->findOneBy(['telephone' => $telephoneParent])
            : null;

        if (!$parent) {
            $parent = new Parents();

            $parent
                ->setNom($inscription->getNomParent())
                ->setPostnom($inscription->getPostnomParent())
                ->setPrenom($inscription->getPrenomParent())
                ->setTelephone(
                    $telephoneParent !== ''
                        ? $telephoneParent
                        : 'Non renseigné'
                );

            $entityManager->persist($parent);
        }

        // 6. Transférer aussi les informations du parent,
        // y compris son e-mail, même s'il existe déjà.
        $parent
            ->setEmail($inscription->getEmailParent())
            ->setAdresse(
                $inscription->getAdresseParent() ?? 'Non renseignée'
            )
            ->setProfession($inscription->getProfessionParent())
            ->setType($inscription->getLienParental());

        // 7. Associer le parent à l'élève.
        $eleve->addParent($parent);

        // 8. Valider l'inscription.
        $inscription
            ->setStatut('validee')
            ->setDateValidation(new \DateTime());

        $entityManager->flush();

        $this->addFlash(
            'success',
            'Inscription validée : élève, classe, option et parent enregistrés.'
        );

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
    public function delete(
        Request $request,
        InscriptionEleve $inscriptionEleve,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid(
            'delete' . $inscriptionEleve->getId(),
            $request->request->get('_token')
        )) {
            $entityManager->remove(
                $inscriptionEleve
            );
            $entityManager->flush();
        }
        return $this->redirectToRoute('app_inscription_eleve_index');
    }

    #[Route('/ajax/sections/{ecole}', name: 'ajax_sections', methods: ['GET'])]
    public function ajaxSections(Ecole $ecole): JsonResponse
    {
        $sections = $this->entityManager
            ->getRepository(Section::class)
            ->findBy(
                ['ecole' => $ecole],
                ['nom' => 'ASC']
            );

        return $this->json(
            array_map(
                fn(Section $section) => [
                    'id' => $section->getId(),
                    'nom' => $section->getNom(),
                ],
                $sections
            )
        );
    }

    #[Route('/ajax/annees/{ecole}', name: 'ajax_annees', methods: ['GET'])]
    public function ajaxAnnees(Ecole $ecole): JsonResponse
    {
        $annees = $this->entityManager
            ->getRepository(AnneeScolaire::class)
            ->findBy(
                ['ecole' => $ecole],
                ['libelle' => 'DESC']
            );

        return $this->json(
            array_map(
                fn(AnneeScolaire $annee) => [
                    'id' => $annee->getId(),
                    'libelle' => $annee->getLibelle(),
                ],
                $annees
            )
        );
    }

    #[Route('/ajax/classes/{section}', name: 'ajax_classes', methods: ['GET'])]
    public function ajaxClasses(Section $section): JsonResponse
    {
        $classes = $this->entityManager
            ->getRepository(Classe::class)
            ->findBy(
                ['section' => $section],
                ['nom' => 'ASC']
            );

        return $this->json(
            array_map(
                fn(Classe $classe) => [
                    'id' => $classe->getId(),
                    'nom' => $classe->getNom(),
                ],
                $classes
            )
        );
    }

    #[Route('/ajax/options/{classe}', name: 'ajax_options', methods: ['GET'])]
    public function ajaxOptions(Classe $classe): JsonResponse
    {
        $options = $this->entityManager
            ->getRepository(Option::class)
            ->findBy(
                ['classe' => $classe],
                ['nom' => 'ASC']
            );

        return $this->json(
            array_map(
                fn(Option $option) => [
                    'id' => $option->getId(),
                    'nom' => $option->getNom(),
                ],
                $options
            )
        );
    }

   
}
