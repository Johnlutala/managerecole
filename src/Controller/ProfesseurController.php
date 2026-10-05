<?php

namespace App\Controller;

use App\Entity\Professeur;
use App\Entity\User;
use App\Entity\AnneeScolaire;
use App\Entity\Cours;
use App\Entity\Section;
use App\Form\ProfesseurType;
use App\Repository\AnneeScolaireRepository;
use App\Repository\EcoleRepository;
use App\Repository\ProfesseurRepository;
use App\Repository\SectionRepository;
use App\Service\PasswordGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/professeur', name: 'app_professeur_')]
final class ProfesseurController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        ProfesseurRepository $repository,
        EntityManagerInterface $entityManager
    ): Response {
        $professeur = new Professeur();
        $form = $this->createForm(ProfesseurType::class, $professeur);
        $form->handleRequest($request);

        foreach ($form->getErrors(true) as $error) {
            dd(
                'Champ : ' . ($error->getOrigin()?->getName() ?? 'formulaire'),
                'Erreur : ' . $error->getMessage()
            );
        }


        if ($form->isSubmitted() && $form->isValid()) {

            if ($this->getUser() instanceof User) {
                $professeur->setCreatedBy($this->getUser());
            }
            $entityManager->persist($professeur);
            $entityManager->flush();
            $this->addFlash('success', 'Professeur créé(e) avec succès.');
            return $this->redirectToRoute('app_professeur_index');
        }
        return $this->render('professeur/index.html.twig', ['professeur' => $professeur, 'professeurs' => $repository->findAll(), 'form' => $form->createView()]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $professeur = new Professeur();
        $form = $this->createForm(ProfesseurType::class, $professeur);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->getUser() instanceof User) {
                $professeur->setCreatedBy($this->getUser());
            }
            $entityManager->persist($professeur);
            $entityManager->flush();
            $this->addFlash('success', 'Professeur créé(e) avec succès.');
            return $this->redirectToRoute('app_professeur_index');
        }
        return $this->render('professeur/new.html.twig', ['professeur' => $professeur, 'form' => $form]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(
        Professeur $professeur,
        EntityManagerInterface $entityManager
    ): Response {
        $cours = $entityManager
            ->getRepository(Cours::class)
            ->findBy(
                ['professeur' => $professeur],
                ['id' => 'ASC']
            );

        return $this->render('professeur/show.html.twig', [
            'professeur' => $professeur,
            'cours' => $cours,
        ]);
    }

    #[Route('/{id}/create-account', name: 'create_account', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function createAccount(
        Request $request,
        Professeur $professeur,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        PasswordGenerator $passwordGenerator,
        MailerInterface $mailer,
        LoggerInterface $logger,
    ): Response {
        if (!$this->isCsrfTokenValid('create-account-professeur' . $professeur->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if ($professeur->getUser()) {
            $this->addFlash('warning', 'Un compte existe déjà pour ce professeur.');

            return $this->accountRedirect($request, $professeur->getId());
        }

        if (!$professeur->getEmail() || !$professeur->getMatricule()) {
            $this->addFlash('danger', 'Ajoutez une adresse e-mail avant de créer le compte.');

            return $this->accountRedirect($request, $professeur->getId());
        }

        $plainPassword = $passwordGenerator->generate();
        $user = (new User())
            ->setUsername($professeur->getMatricule())
            ->setEmail($professeur->getEmail())
            ->setFirstname($professeur->getPrenom())
            ->setLastname($professeur->getNom())
            ->setRoles(['ROLE_PROFESSEUR']);
        $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
        if ($this->getUser() instanceof User) {
            $user->setCreatedBy($this->getUser());
        }
        $professeur->setUser($user);

        $entityManager->persist($user);
        $entityManager->flush();

        try {
            $this->sendCredentialsEmail($user, $plainPassword, $mailer, 'Création du compte professeur');
            $this->addFlash('success', 'Compte professeur créé et identifiants envoyés par e-mail.');
        } catch (\Throwable $exception) {
            $logger->error('Échec de l’envoi des identifiants du professeur.', [
                'user_id' => $user->getId(),
                'error' => $exception->getMessage(),
            ]);
            $this->addFlash('warning', 'Compte créé, mais l’e-mail des identifiants n’a pas pu être envoyé.');
        }

        return $this->accountRedirect($request, $professeur->getId());
    }

    #[Route('/{id}/reset-account', name: 'reset_account', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function resetAccount(
        Request $request,
        Professeur $professeur,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        PasswordGenerator $passwordGenerator,
        MailerInterface $mailer,
        LoggerInterface $logger,
    ): Response {
        if (!$this->isCsrfTokenValid('reset-account-professeur' . $professeur->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $user = $professeur->getUser();
        if (!$user) {
            $this->addFlash('warning', 'Aucun compte n’existe pour ce professeur.');

            return $this->accountRedirect($request, $professeur->getId());
        }

        if (!$professeur->getEmail()) {
            $this->addFlash('danger', 'Ajoutez une adresse e-mail avant de réinitialiser les identifiants.');

            return $this->accountRedirect($request, $professeur->getId());
        }

        $plainPassword = $passwordGenerator->generate();
        $user
            ->setEmail($professeur->getEmail())
            ->setEnabled(true)
            ->setPassword($passwordHasher->hashPassword($user, $plainPassword));
        $entityManager->flush();

        try {
            $this->sendCredentialsEmail($user, $plainPassword, $mailer, 'Réinitialisation du compte professeur');
            $this->addFlash('success', 'Mot de passe réinitialisé et compte activé. Les nouveaux identifiants ont été envoyés par e-mail.');
        } catch (\Throwable $exception) {
            $logger->error('Échec de l’envoi des nouveaux identifiants du professeur.', [
                'user_id' => $user->getId(),
                'error' => $exception->getMessage(),
            ]);
            $this->addFlash('warning', 'Mot de passe réinitialisé et compte activé, mais l’e-mail n’a pas pu être envoyé.');
        }

        return $this->accountRedirect($request, $professeur->getId());
    }

    #[Route('/{id}/close-account', name: 'close_account', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function closeAccount(Request $request, Professeur $professeur, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('close-account-professeur' . $professeur->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if ($professeur->getUser()) {
            $professeur->getUser()->setEnabled(false);
            $entityManager->flush();
            $this->addFlash('success', 'Le compte professeur a été fermé.');
        } else {
            $this->addFlash('warning', 'Aucun compte n’existe pour ce professeur.');
        }

        return $this->accountRedirect($request, $professeur->getId());
    }

    private function accountRedirect(Request $request, int $professeurId): Response
    {
        if ($request->request->getString('_return_to') === 'index') {
            return $this->redirectToRoute('app_professeur_index');
        }

        return $this->redirectToRoute('app_professeur_show', ['id' => $professeurId]);
    }

    private function sendCredentialsEmail(User $user, string $plainPassword, MailerInterface $mailer, string $operation): void
    {
        $mailer->send(
            (new TemplatedEmail())
                ->from(new Address($_ENV['SENDER_EMAIL'], $_ENV['SENDER_NAME']))
                ->to($user->getEmail())
                ->subject('Vos identifiants de connexion')
                ->htmlTemplate('email/user_create.html.twig')
                ->context([
                    'user' => $user,
                    'username' => $user->getUsername(),
                    'password' => $plainPassword,
                    'operation' => $operation,
                ])
        );
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Professeur $professeur, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProfesseurType::class, $professeur);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($professeur->getUser() && $professeur->getEmail()) {
                $professeur->getUser()->setEmail($professeur->getEmail());
            }
            $entityManager->flush();
            $this->addFlash('success', 'Professeur mis(e) à jour avec succès.');
            return $this->redirectToRoute('app_professeur_index');
        }
        return $this->render('professeur/edit.html.twig', ['professeur' => $professeur, 'form' => $form]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Professeur $professeur, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $professeur->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($professeur);
            $entityManager->flush();
            $this->addFlash('success', 'Professeur supprimé(e) avec succès.');
        }
        return $this->redirectToRoute('app_professeur_index');
    }

    #[Route('/dependances/{id}', name: 'dependances', methods: ['GET'])]
    public function dependances(
        int $id,
        EcoleRepository $ecoleRepository,
        AnneeScolaireRepository $anneeScolaireRepository,
        SectionRepository $sectionRepository
    ): JsonResponse {
        $ecole = $ecoleRepository->find($id);

        if (!$ecole) {
            return $this->json([
                'success' => false,
                'message' => 'École introuvable.'
            ], 404);
        }

        $annees = $anneeScolaireRepository->findBy([
            'ecole' => $ecole
        ]);

        $sections = $sectionRepository->findBy([
            'ecole' => $ecole
        ]);

        return $this->json([
            'success' => true,

            'annees' => array_map(function (AnneeScolaire $annee) {
                return [
                    'id' => $annee->getId(),
                    'label' => $annee->getLibelle(),
                ];
            }, $annees),

            'sections' => array_map(function (Section $section) {
                return [
                    'id' => $section->getId(),
                    'label' => $section->getNom(),
                ];
            }, $sections),
        ]);
    }
}
